<?php

namespace App\Http\Controllers;

use App\Jobs\ScanNetworkCameras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class NetworkScanController extends Controller
{
    private const ALLOWED_EMAIL = 'azizbektursunovofficial@gmail.com';

    private function authorizeOwner(): void
    {
        if (auth()->user()?->email !== self::ALLOWED_EMAIL) {
            abort(404);
        }
    }

    public function index()
    {
        $this->authorizeOwner();

        return Inertia::render('Security/NetworkScan', [
            'results' => Cache::get('network_scan_results'),
            'scanRunning' => Cache::get('network_scan_running', false),
            'lastScanAt' => Cache::get('network_scan_completed_at'),
        ]);
    }

    public function scan()
    {
        $this->authorizeOwner();

        if (Cache::get('network_scan_running')) {
            return back()->with('error', 'Skanerlash allaqachon ishlayapti.');
        }

        Cache::put('network_scan_running', true, 600);

        ScanNetworkCameras::dispatch();

        return back()->with('success', 'Tarmoq skanerlash boshlandi. Bu 5-10 daqiqa davom etishi mumkin.');
    }

    public function preview(Request $request)
    {
        $this->authorizeOwner();

        $request->validate([
            'ip' => 'required|ip',
            'port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'stream_path' => 'nullable|string',
        ]);

        $ip = $request->input('ip');
        $port = $request->input('port', 554);
        $username = $request->input('username', '');
        $password = $request->input('password', '');
        $streamPath = ltrim($request->input('stream_path', 'Streaming/Channels/101'), '/');

        $auth = ($username && $password) ? "{$username}:{$password}@" : '';
        $rtspUrl = "rtsp://{$auth}{$ip}:{$port}/{$streamPath}";

        $pathName = 'preview_'.str_replace('.', '_', $ip);

        $mtxHost = config('services.mediamtx.host', 'localhost');
        $mtxPort = config('services.mediamtx.port', '9997');
        $mtxUser = config('services.mediamtx.user', 'admin');
        $mtxPass = config('services.mediamtx.password');

        $baseUrl = "http://{$mtxUser}:{$mtxPass}@{$mtxHost}:{$mtxPort}/v3";

        $ffmpegCmd = "/usr/bin/ffmpeg -hide_banner -loglevel warning"
            ." -fflags nobuffer -flags low_delay -analyzeduration 500000 -probesize 500000"
            ." -rtsp_transport tcp -i {$rtspUrl}"
            ." -c:v copy -c:a libopus -b:a 48k -f rtsp rtsp://{$mtxUser}:{$mtxPass}@127.0.0.1:8554/{$pathName}";

        $payload = [
            'source' => 'publisher',
            'runOnDemand' => "sh -c '{$ffmpegCmd} > /tmp/ffmpeg_preview.log 2>&1'",
            'runOnDemandRestart' => true,
            'runOnDemandStartTimeout' => '10s',
            'runOnDemandCloseAfter' => '30s',
        ];

        // Remove existing path first, then add
        Http::timeout(5)->delete("{$baseUrl}/config/paths/delete/{$pathName}");
        $response = Http::timeout(5)->post("{$baseUrl}/config/paths/add/{$pathName}", $payload);

        if ($response->failed() && $response->status() !== 409) {
            Log::error('NetworkScan preview: Failed to add MediaMTX path', [
                'ip' => $ip,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json(['error' => 'MediaMTX path yaratib bo\'lmadi'], 500);
        }

        return response()->json([
            'path' => $pathName,
            'hlsUrl' => "/hls/{$pathName}/index.m3u8",
            'whepUrl' => "/webrtc/{$pathName}/whep",
        ]);
    }

    public function stopPreview(Request $request)
    {
        $this->authorizeOwner();

        $request->validate(['ip' => 'required|ip']);

        $pathName = 'preview_'.str_replace('.', '_', $request->input('ip'));

        $mtxHost = config('services.mediamtx.host', 'localhost');
        $mtxPort = config('services.mediamtx.port', '9997');
        $mtxUser = config('services.mediamtx.user', 'admin');
        $mtxPass = config('services.mediamtx.password');

        $baseUrl = "http://{$mtxUser}:{$mtxPass}@{$mtxHost}:{$mtxPort}/v3";

        Http::timeout(5)->delete("{$baseUrl}/config/paths/delete/{$pathName}");

        return response()->json(['ok' => true]);
    }
}
