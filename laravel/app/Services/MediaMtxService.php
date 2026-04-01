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
        $pathName = $this->getPathName($camera);
        $rtspUrl = $camera->rtsp_url;
        $mtxUser = config('services.mediamtx.user');
        $mtxPass = config('services.mediamtx.password');

        // FFmpeg relay with fast-start flags for quick on-demand startup.
        // -c:v copy = no video transcoding (near 0% CPU)
        // -c:a libopus = transcode audio to Opus (works with both WebRTC and HLS)
        // -frame_duration 20 = fixed 20ms Opus frames → stable HLS part durations
        // -fflags nobuffer -flags low_delay = reduce startup latency
        $safeRtspUrl = escapeshellarg($rtspUrl);
        $safeMtxUser = escapeshellarg($mtxUser);
        $safeMtxPass = escapeshellarg($mtxPass);
        $ffmpegCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
            ." -fflags nobuffer -flags low_delay -analyzeduration 500000 -probesize 500000"
            ." -rtsp_transport tcp -i {$safeRtspUrl}"
            ." -c:v copy -c:a libopus -b:a 48k -frame_duration 20 -f rtsp rtsp://{$safeMtxUser}:{$safeMtxPass}@127.0.0.1:8554/{$pathName}";

        $payload = [
            'source' => 'publisher',
            'runOnDemand' => "sh -c '{$ffmpegCmd} > /tmp/ffmpeg_{$camera->id}.log 2>&1'",
            'runOnDemandRestart' => true,
            'runOnDemandStartTimeout' => '10s',
            'runOnDemandCloseAfter' => '120s',
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // YouTube RTMP push starts when stream is ready, uses already-transcoded AAC audio
            $safeYtUrl = escapeshellarg($camera->youtube_url);
            $ytCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
                ." -i rtsp://{$safeMtxUser}:{$safeMtxPass}@127.0.0.1:8554/{$pathName}"
                ." -c copy -f flv {$safeYtUrl}";
            $payload['runOnReady'] = "sh -c '{$ytCmd} > /tmp/yt_{$camera->id}.log 2>&1'";
            $payload['runOnReadyRestart'] = true;
        }

        Log::info('MediaMTX: Adding path', ['camera_id' => $camera->id, 'path' => $pathName]);

        $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/config/paths/add/{$pathName}", $payload);

        if ($response->failed() && $response->status() !== 409) {
            Log::error('MediaMTX: Failed to add path', [
                'camera_id' => $camera->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Failed to add path to MediaMTX: '.$response->body());
        } elseif ($response->status() === 409) {
            Log::info('MediaMTX: Path already exists, updating', ['camera_id' => $camera->id]);
            $this->updatePath($camera);
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
        $pathName = $this->getPathName($camera);

        Log::info('MediaMTX: Updating path via PATCH', ['camera_id' => $camera->id]);

        $rtspUrl = $camera->rtsp_url;
        $mtxUser = config('services.mediamtx.user');
        $mtxPass = config('services.mediamtx.password');

        $safeRtspUrl = escapeshellarg($rtspUrl);
        $safeMtxUser = escapeshellarg($mtxUser);
        $safeMtxPass = escapeshellarg($mtxPass);
        $ffmpegCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
            ." -fflags nobuffer -flags low_delay -analyzeduration 500000 -probesize 500000"
            ." -rtsp_transport tcp -i {$safeRtspUrl}"
            ." -c:v copy -c:a libopus -b:a 48k -frame_duration 20 -f rtsp rtsp://{$safeMtxUser}:{$safeMtxPass}@127.0.0.1:8554/{$pathName}";

        $payload = [
            'runOnDemand' => "sh -c '{$ffmpegCmd} > /tmp/ffmpeg_{$camera->id}.log 2>&1'",
            'runOnDemandRestart' => true,
            'runOnDemandStartTimeout' => '10s',
            'runOnDemandCloseAfter' => '120s',
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            $safeYtUrl = escapeshellarg($camera->youtube_url);
            $ytCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
                ." -i rtsp://{$safeMtxUser}:{$safeMtxPass}@127.0.0.1:8554/{$pathName}"
                ." -c copy -f flv {$safeYtUrl}";
            $payload['runOnReady'] = "sh -c '{$ytCmd} > /tmp/yt_{$camera->id}.log 2>&1'";
            $payload['runOnReadyRestart'] = true;
        }

        $response = Http::timeout($this->timeout)->patch("{$this->baseUrl}/config/paths/patch/{$pathName}", $payload);

        if ($response->failed()) {
            Log::error('MediaMTX: Failed to patch path', [
                'camera_id' => $camera->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Failed to patch path in MediaMTX: '.$response->body());
        }
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
}
