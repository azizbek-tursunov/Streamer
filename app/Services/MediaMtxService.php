<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Camera;
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
        
        // Direct Copy mode for efficiency (near 0% CPU)
        // NOTE: Camera MUST be set to H.264 manually for browser playback
        // Using -c:a aac to ensure audio is supported by browsers (AAC)
        // Using 127.0.0.1 to avoid IPv6 issues
        // Wrapped in sh -c to ensure redirection > works. Using single quotes for sh -c.
        $innerCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning -rtsp_transport tcp -i {$rtspUrl} -c:v copy -c:a aac -f rtsp rtsp://" . config('services.mediamtx.user') . ":" . config('services.mediamtx.password') . "@127.0.0.1:8554/{$pathName}";
        
        $ffmpegCmd = "sh -c '{$innerCmd} > /tmp/ffmpeg_{$camera->id}.log 2>&1'";

        $payload = [
            'source' => 'publisher',
            'runOnInit' => $ffmpegCmd,
            'runOnInitRestart' => true,
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // Youtube stream
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
            throw new RuntimeException("Failed to add path to MediaMTX: " . $response->body());
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
        return 'cam_' . $camera->id;
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

