<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NetworkScanController extends Controller
{
    private const ALLOWED_EMAIL = 'azizbektursunovofficial@gmail.com';

    /**
     * Subnets where cameras are known to exist.
     */
    private array $subnets = [
        '192.168.40',
        '192.168.64',
        '192.168.90',
        '192.168.15',
    ];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()?->email !== self::ALLOWED_EMAIL) {
                abort(404);
            }

            return $next($request);
        });
    }

    public function index()
    {
        $results = Cache::get('network_scan_results');
        $scanRunning = Cache::get('network_scan_running', false);

        return Inertia::render('Security/NetworkScan', [
            'results' => $results,
            'scanRunning' => $scanRunning,
            'lastScanAt' => Cache::get('network_scan_completed_at'),
        ]);
    }

    public function scan()
    {
        if (Cache::get('network_scan_running')) {
            return back()->with('error', 'Skanerlash allaqachon ishlayapti.');
        }

        Cache::put('network_scan_running', true, 600); // 10 min max

        $subnets = $this->subnets;

        dispatch(function () use ($subnets) {
            $knownIps = Camera::pluck('ip_address')->toArray();
            $found = [];

            foreach ($subnets as $subnet) {
                for ($i = 1; $i <= 254; $i++) {
                    $ip = "{$subnet}.{$i}";

                    if (in_array($ip, $knownIps)) {
                        continue;
                    }

                    // Quick TCP connect to RTSP port (554)
                    $socket = @fsockopen($ip, 554, $errno, $errstr, 0.3);
                    if ($socket) {
                        fclose($socket);

                        $info = ['ip' => $ip, 'port' => 554, 'rtsp_verified' => false, 'details' => null];

                        // Try ffprobe to get stream info
                        $rtspUrl = "rtsp://{$ip}:554";
                        $cmd = sprintf(
                            'ffprobe -v quiet -print_format json -show_streams -rtsp_transport tcp -timeout 3000000 %s 2>/dev/null',
                            escapeshellarg($rtspUrl)
                        );
                        $output = shell_exec($cmd);
                        if ($output) {
                            $data = json_decode($output, true);
                            if (! empty($data['streams'])) {
                                $info['rtsp_verified'] = true;
                                $info['details'] = collect($data['streams'])->map(fn ($s) => [
                                    'codec' => $s['codec_name'] ?? null,
                                    'type' => $s['codec_type'] ?? null,
                                    'resolution' => isset($s['width']) ? "{$s['width']}x{$s['height']}" : null,
                                ])->toArray();
                            }
                        }

                        // Also check port 80 for web interface
                        $http = @fsockopen($ip, 80, $errno, $errstr, 0.3);
                        if ($http) {
                            fclose($http);
                            $info['has_web'] = true;
                        }

                        $found[] = $info;
                    }
                }
            }

            Cache::put('network_scan_results', $found, 86400); // 24h
            Cache::put('network_scan_completed_at', now()->toDateTimeString(), 86400);
            Cache::forget('network_scan_running');
        })->onQueue('default');

        return back()->with('success', 'Tarmoq skanerlash boshlandi. Bu 5-10 daqiqa davom etishi mumkin.');
    }
}
