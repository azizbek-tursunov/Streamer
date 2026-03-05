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
        $pass = config('services.mediamtx.password', '12345');

        $this->baseUrl = "http://{$user}:{$pass}@{$host}:{$port}/v3";
    }

    public function addPath(Camera $camera): void
    {
        $pathName = $this->getPathName($camera);

        // Native RTSP source proxy: MediaMTX pulls directly from camera.
        // No FFmpeg middleman = faster startup (~1s vs ~3s).
        // sourceOnDemand: only connects when a viewer requests the stream.
        $payload = [
            'source' => $camera->rtsp_url,
            'sourceProtocol' => 'tcp',
            'sourceOnDemand' => true,
            'sourceOnDemandStartTimeout' => '10s',
            'sourceOnDemandCloseAfter' => '30s',
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // YouTube needs FFmpeg for RTMP push + AAC audio transcoding.
            // Runs only when the stream is ready (viewer watching or YouTube active).
            $ytCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
                ." -i rtsp://".config('services.mediamtx.user').':'.config('services.mediamtx.password')."@127.0.0.1:8554/{$pathName}"
                ." -c:v copy -c:a aac -f flv \"{$camera->youtube_url}\"";
            $payload['runOnReady'] = "sh -c '{$ytCmd} > /tmp/yt_{$camera->id}.log 2>&1'";
            $payload['runOnReadyRestart'] = true;
            // Keep stream always on so YouTube doesn't drop
            $payload['sourceOnDemand'] = false;
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
        Log::info('MediaMTX: Updating path', ['camera_id' => $camera->id]);

        $this->removePath($camera);
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
}
