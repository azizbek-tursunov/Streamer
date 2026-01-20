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
        
        // Smart Encoding Logic
        // 1. If Rotation is active -> MUST Transcode (Filters require decoding).
        // 2. If Codec is NOT H.264 (e.g. HEVC) -> MUST Transcode (Browser/YouTube compatibility).
        // 3. If Codec IS H.264 AND No Rotation -> DIRECT COPY (Low CPU).
        
        $shouldTranscode = false;
        $filters = '';
        
        // Check Rotation
        if ($camera->rotation) {
            $shouldTranscode = true;
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
        
        // Check Codec if not already forced to transcode
        // If codec is unknown (null), we default to transcoding for safety.
        if (!$shouldTranscode) {
             // We treat 'h264' (case-insensitive) as safe to copy.
             // Note: ffprobe usually returns 'h264'.
             $isH264 = $camera->video_codec && strtolower($camera->video_codec) === 'h264';
             
             if (!$isH264) {
                 $shouldTranscode = true;
             }
        }

        // Construct Command
        if ($shouldTranscode) {
             // Transcode Mode (High CPU, High Compatibility, Supports Rotation)
             $ffmpegCmd = "ffmpeg -hide_banner -loglevel error -rtsp_transport tcp -i \"$rtspUrl\" $filters -c:v libx264 -preset ultrafast -tune zerolatency -b:v 1000k -c:a aac -f rtsp \"$publishUrl\"";
        } else {
             // Copy Mode (Low CPU, Original Quality, No Rotation)
             // We Copy Video (-c:v copy) but ensure Audio is AAC (-c:a aac) just in case source audio is weird (PCM etc).
             $ffmpegCmd = "ffmpeg -hide_banner -loglevel error -rtsp_transport tcp -i \"$rtspUrl\" -c:v copy -c:a aac -f rtsp \"$publishUrl\"";
        }

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

    public function getStreamInfo(Camera $camera): ?string
    {
        $rtspUrl = $camera->rtsp_url;
        // Basic escaping regarding quotes
        $rtspUrl = str_replace('"', '\"', $rtspUrl);
        
        // Run ffprobe in the container
        // We set a timeout of 10s
        $command = "docker exec mediamtx ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 \"$rtspUrl\"";
        
        try {
            $result = \Illuminate\Support\Facades\Process::timeout(15)->run($command);
            
            if ($result->successful()) {
                $codec = trim($result->output());
                // Update camera
                if ($codec) {
                    $camera->update(['video_codec' => $codec]);
                }
                return $codec;
            }
        } catch (\Exception $e) {
            // Log error?
        }
        
        return null;
    }
}
