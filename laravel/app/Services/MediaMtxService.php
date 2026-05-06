<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MediaMtxService
{
    protected string $baseUrl;

    protected int $timeout = 10;

    public function __construct()
    {
        $host = config('services.mediamtx.host', 'localhost');
        $port = config('services.mediamtx.port', '9997');
        $user = config('services.mediamtx.user', 'admin');
        $pass = config('services.mediamtx.password');

        $this->baseUrl = "http://{$user}:{$pass}@{$host}:{$port}/v3";
    }

    public function addPath(Camera $camera): void
    {
        $payload = $this->buildPayload($camera);
        $pathName = $this->getPathName($camera);

        Log::info('MediaMTX: Upserting path', ['camera_id' => $camera->id, 'path' => $pathName]);

        // PATCH updates an existing path atomically; POST creates a new one.
        // MediaMTX returns 404 on PATCH if the path doesn't exist yet.
        $response = Http::timeout($this->timeout)->patch("{$this->baseUrl}/config/paths/patch/{$pathName}", $payload);

        if ($response->status() === 404) {
            // Path doesn't exist yet — create it
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/config/paths/add/{$pathName}", $payload);
        }

        if ($response->failed()) {
            Log::error('MediaMTX: Failed to upsert path', [
                'camera_id' => $camera->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Failed to upsert path in MediaMTX: '.$response->body());
        }
    }

    public function removePath(Camera $camera): void
    {
        $pathName = $this->getPathName($camera);

        Log::info('MediaMTX: Removing path', ['camera_id' => $camera->id, 'path' => $pathName]);

        Http::timeout($this->timeout)->delete("{$this->baseUrl}/config/paths/delete/{$pathName}");
    }

    public function updatePath(Camera $camera): void
    {
        $this->addPath($camera);
    }

    public function getPathName(Camera $camera): string
    {
        return 'cam_'.$camera->id;
    }

    /**
     * Check if MediaMTX is reachable (for health checks)
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/paths/list");

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('MediaMTX: Health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listPathStates(): array
    {
        $states = [];
        $page = 0;
        $pageCount = 1;

        do {
            $response = Http::timeout(5)->get("{$this->baseUrl}/paths/list", [
                'page' => $page,
            ]);

            if ($response->failed()) {
                throw new RuntimeException('Failed to list MediaMTX paths: '.$response->body());
            }

            $data = $response->json();
            $pageCount = max((int) ($data['pageCount'] ?? 1), 1);

            foreach (($data['items'] ?? []) as $item) {
                if (isset($item['name'])) {
                    $states[$item['name']] = $item;
                }
            }

            $page++;
        } while ($page < $pageCount);

        return $states;
    }

    private function buildPayload(Camera $camera): array
    {
        $pathName = $this->getPathName($camera);
        $mtxUser = config('services.mediamtx.user');
        $mtxPass = config('services.mediamtx.password');

        // FFmpeg relay with fast-start flags for quick on-demand startup.
        // -c:v copy = no video transcoding (near 0% CPU)
        // -c:a libopus -frame_duration 20 = fixed 20ms Opus frames → stable HLS part durations
        // -fflags nobuffer -flags low_delay = reduce startup latency
        $safeRtspUrl = escapeshellarg($camera->rtsp_url);
        $safeMtxUser = escapeshellarg($mtxUser);
        $safeMtxPass = escapeshellarg($mtxPass);
        $ffmpegCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
            ." -fflags nobuffer -flags low_delay -analyzeduration 100000 -probesize 100000"
            ." -rtsp_transport tcp -i {$safeRtspUrl}"
            ." -c:v copy -c:a libopus -b:a 48k -frame_duration 20"
            ." -max_interleave_delta 0"
            ." -f rtsp rtsp://{$safeMtxUser}:{$safeMtxPass}@127.0.0.1:8554/{$pathName}";

        $payload = [
            'source' => 'publisher',
            'runOnDemand' => "sh -c '{$ffmpegCmd} > /tmp/ffmpeg_{$camera->id}.log 2>&1'",
            'runOnDemandRestart' => true,
            'runOnDemandStartTimeout' => '10s',
            'runOnDemandCloseAfter' => '5m',
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // YouTube RTMP/FLV requires AAC audio; the local stream uses Opus for browser playback.
            $safeYtUrl = escapeshellarg($camera->youtube_url);
            $ytCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
                ." -rtsp_transport tcp -i rtsp://{$safeMtxUser}:{$safeMtxPass}@127.0.0.1:8554/{$pathName}"
                ." -c:v copy -c:a aac -ar 44100 -b:a 128k"
                ." -f flv {$safeYtUrl}";
            $payload['runOnReady'] = "sh -c '{$ytCmd} > /tmp/yt_{$camera->id}.log 2>&1'";
            $payload['runOnReadyRestart'] = true;
        }

        return $payload;
    }
}
