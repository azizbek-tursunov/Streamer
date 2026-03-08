<?php

namespace App\Jobs;

use App\Models\Camera;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ScanNetworkCameras implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    private array $subnets = [
        '192.168.40',
        '192.168.64',
        '192.168.90',
        '192.168.15',
    ];

    public function handle(): void
    {
        $knownIps = Camera::pluck('ip_address')->toArray();
        $found = [];

        foreach ($this->subnets as $subnet) {
            for ($i = 1; $i <= 254; $i++) {
                $ip = "{$subnet}.{$i}";

                if (in_array($ip, $knownIps)) {
                    continue;
                }

                $socket = @fsockopen($ip, 554, $errno, $errstr, 0.3);
                if ($socket) {
                    fclose($socket);

                    $info = ['ip' => $ip, 'port' => 554, 'rtsp_verified' => false, 'details' => null];

                    $cmd = sprintf(
                        'ffprobe -v quiet -print_format json -show_streams -rtsp_transport tcp -timeout 3000000 %s 2>/dev/null',
                        escapeshellarg("rtsp://{$ip}:554")
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

                    $http = @fsockopen($ip, 80, $errno, $errstr, 0.3);
                    if ($http) {
                        fclose($http);
                        $info['has_web'] = true;
                    }

                    $found[] = $info;
                }
            }
        }

        Cache::put('network_scan_results', $found, 86400);
        Cache::put('network_scan_completed_at', now()->toDateTimeString(), 86400);
        Cache::forget('network_scan_running');
    }
}
