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
        $rtspUrl = $camera->rtsp_url;

        // On-demand FFmpeg relay: starts ONLY when a viewer requests this stream,
        // stops 30 seconds after the last viewer disconnects.
        // This scales to 200+ cameras (only ~5-15 active at any time).
        //
        // -c:v copy = no video transcoding (near 0% CPU)
        // -c:a aac = transcode audio to AAC for browser compatibility
        // Using 127.0.0.1 to avoid IPv6 issues
        $innerCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning -rtsp_transport tcp -i {$rtspUrl} -c:v copy -c:a aac -f rtsp rtsp://".config('services.mediamtx.user').':'.config('services.mediamtx.password')."@127.0.0.1:8554/{$pathName}";

        $ffmpegCmd = "sh -c '{$innerCmd} > /tmp/ffmpeg_{$camera->id}.log 2>&1'";

        $payload = [
            'source' => 'publisher',
            'runOnDemand' => $ffmpegCmd,
            'runOnDemandRestart' => true,
            'runOnDemandStartTimeout' => '10s',
            'runOnDemandCloseAfter' => '30s',
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // YouTube stream: starts when the on-demand stream becomes ready
            $payload['runOnReady'] = "sh -c '/usr/bin/ffmpeg -i rtsp://127.0.0.1:8554/{$pathName} -c copy -f flv \"{$camera->youtube_url}\" >> /tmp/ffmpeg_{$camera->id}.log 2>&1'";
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
