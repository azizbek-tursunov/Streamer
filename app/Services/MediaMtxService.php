<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Camera;
use RuntimeException;

class MediaMtxService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://admin:12345@localhost:9997/v3';
    }

    public function addPath(Camera $camera): void
    {
        // Add the camera as a path in MediaMTX
        // We use the camera ID as the path name, e.g., "cam_1"
        $pathName = $this->getPathName($camera);

        $payload = [
            'source' => $camera->rtsp_url,
            'sourceOnDemand' => false,
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // Add runOnReady command to push to YouTube
            // MediaMTX uses FFmpeg to restream.
            // Command to read from the RTSP source (which MediaMTX proxies) and push to RTMP
            // Note: MediaMTX environment variables are available in runOnReady.
            // $RTSP_PATH is the path name.
            
            // However, a simpler way with MediaMTX is to use 'runOnReady' to start a ffmpeg process that pulls from the LOCAL localhost RTSP feed and pushes to YouTube.
            // Local RTSP feed for this path: rtsp://localhost:8554/$pathName
            
            // IMPORTANT: We need to ensure ffmpeg is installed in the MediaMTX container or use the built-in capability if it acts as a proxy.
            // The official MediaMTX image contains ffmpeg.
            
            // Let's assume we are running inside docker and 'ffmpeg' is available.
            // For YouTube RTMP, we need to send video and audio.
            // Using -c copy is efficient if the source codecs are compatible (H264/AAC).
            // If not, we might need transcoding, but let's start with copy for performance.
            
            $payload['runOnReady'] = "ffmpeg -i rtsp://localhost:8554/$pathName -c copy -f flv \"{$camera->youtube_url}\"";
            $payload['runOnReadyRestart'] = true;
        }

        $response = Http::post("{$this->baseUrl}/config/paths/add/{$pathName}", $payload);

        if ($response->failed() && $response->status() !== 409) { // 409 means already exists
             throw new RuntimeException("Failed to add path to MediaMTX: " . $response->body());
        } elseif ($response->status() === 409) {
            // If it exists, update it to match new config
            $this->updatePath($camera);
        }
    }

    public function removePath(Camera $camera): void
    {
        $pathName = $this->getPathName($camera);
        Http::delete("{$this->baseUrl}/config/paths/delete/{$pathName}");
    }

    public function updatePath(Camera $camera): void
    {
        $pathName = $this->getPathName($camera);
        
        // It's often easier to replace it than patch it, but v3 api allows replace via POST or PATCH? 
        // Docs say POST /config/paths/add/{name} adds, DELETE /config/paths/delete/{name} removes.
        // There is also PATCH /config/paths/patch/{name}
        
        // Let's try to patch first, if not delete and add.
         $payload = [
            'source' => $camera->rtsp_url,
        ];
        
        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
             $payload['runOnReady'] = "ffmpeg -i rtsp://localhost:8554/$pathName -c copy -f flv \"{$camera->youtube_url}\"";
             $payload['runOnReadyRestart'] = true;
        } else {
            // Remove the command by sending null or empty?
            // MediaMTX config patching might require sending the full config or specific fields.
            // Safest way is often Delete -> Add to ensure clean state for the sub-process.
            $this->removePath($camera);
            $this->addPath($camera);
            return;
        }

        $response = Http::patch("{$this->baseUrl}/config/paths/patch/{$pathName}", $payload);
        
        if ($response->failed()) {
             // Fallback to recreate
            $this->removePath($camera);
            $this->addPath($camera);
        }
    }
    
    public function getPathName(Camera $camera): string
    {
        return 'cam_' . $camera->id;
    }
}
