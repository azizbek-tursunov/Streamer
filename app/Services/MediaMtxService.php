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
        $pathName = $this->getPathName($camera);

        // We use FFmpeg to pull from the camera and publishing to MediaMTX.
        // This solves H.265 incompatibility (invalid DeltaPocS0) by transcoding to H.264.
        // It also ensures a stable connection via TCP.
        
        $rtspUrl = $camera->rtsp_url;
        // Escape the URL for use in shell command
        // $rtspUrl = escapeshellarg($rtspUrl); // Wait, runOnInit is array or string? API takes string. JSON encoded.
        // Be careful with quotes in JSON.
        
        // Command to transcode to H.264 and publish to local MediaMTX
        // Note: connecting to localhost:8554 inside the container.
        // Using `admin:12345` for publishing auth.
        $publishUrl = "rtsp://admin:12345@localhost:8554/$pathName";
        
        // FFmpeg command:
        // -rtsp_transport tcp: Force TCP for stability
        // -i ...: Input
        // -c:v libx264: Transcode to H.264
        // -preset ultrafast -tune zerolatency: Low latency
        // -c:a aac: Audio to AAC
        // -f rtsp ...: Output
        
        $filters = '';
        if ($camera->rotation) {
            $vf = match ((int)$camera->rotation) {
                90 => 'transpose=1',
                180 => 'transpose=1,transpose=1',
                270 => 'transpose=2',
                default => null,
            };
            if ($vf) {
                $filters = "-vf \"$vf\"";
            }
        }
        
        // We use single quotes for the command string in PHP, and double quotes for arguments in the shell command where needed.
        $ffmpegCmd = "ffmpeg -hide_banner -loglevel error -rtsp_transport tcp -i \"$rtspUrl\" $filters -c:v libx264 -preset ultrafast -tune zerolatency -b:v 1000k -c:a aac -f rtsp \"$publishUrl\"";

        $payload = [
            'source' => 'publisher', // MediaMTX waits for the stream
            'runOnInit' => $ffmpegCmd, // This creates the stream
            'runOnInitRestart' => true, // Restart ffmpeg if it crashes
        ];

        if ($camera->is_streaming_to_youtube && $camera->youtube_url) {
            // If streaming to YouTube, we can ADD another ffmpeg process via runOnReady?
            // Or chain it?
            // runOnReady runs when the stream is READY (published).
            // So we can assume $pathName is ready (thanks to runOnInit).
            // We use standard input (rtsp://localhost:8554/$pathName) which is now H.264.
            // YouTube accepts H.264 directly (flv).
            
            $payload['runOnReady'] = "ffmpeg -i rtsp://localhost:8554/$pathName -c copy -f flv \"{$camera->youtube_url}\"";
            $payload['runOnReadyRestart'] = true;
        }

        $response = Http::post("{$this->baseUrl}/config/paths/add/{$pathName}", $payload);

        if ($response->failed() && $response->status() !== 409) {
             throw new RuntimeException("Failed to add path to MediaMTX: " . $response->body());
        } elseif ($response->status() === 409) {
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
        // For simplicity and reliability with runOnInit/runOnReady changes, we Remove then Add.
        // Patching complex configs with runOnInit changes can be flaky.
        $this->removePath($camera);
        $this->addPath($camera);
    }
    
    public function getPathName(Camera $camera): string
    {
        return 'cam_' . $camera->id;
    }
}
