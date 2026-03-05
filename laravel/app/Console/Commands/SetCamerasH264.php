<?php

namespace App\Console\Commands;

use App\Models\Camera;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetCamerasH264 extends Command
{
    protected $signature = 'cameras:set-h264 {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Switch all active Hikvision cameras from H.265 to H.264 via ISAPI';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');
        $cameras = Camera::where('is_active', true)->get();

        if ($cameras->isEmpty()) {
            $this->info('No active cameras found.');
            return;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Checking {$cameras->count()} cameras...");
        $this->newLine();

        $changed = 0;
        $alreadyH264 = 0;
        $failed = 0;

        foreach ($cameras as $camera) {
            $label = "{$camera->name} (ID: {$camera->id}, IP: {$camera->ip_address})";
            $url = "http://{$camera->ip_address}/ISAPI/Streaming/channels/101/video";

            try {
                $http = Http::timeout(10);

                if (! empty($camera->username)) {
                    $http = $http->withDigestAuth($camera->username, $camera->password ?? '');
                }

                // GET current video config
                $response = $http->get($url);

                if (! $response->successful()) {
                    $this->error("  FAIL {$label} - HTTP {$response->status()}");
                    $failed++;
                    continue;
                }

                $xml = $response->body();

                // Check current codec
                if (preg_match('/<videoCodecType>(.*?)<\/videoCodecType>/', $xml, $matches)) {
                    $currentCodec = $matches[1];

                    if (stripos($currentCodec, 'H.264') !== false) {
                        $this->line("  OK   {$label} - Already H.264");
                        $alreadyH264++;
                        continue;
                    }

                    $this->warn("  H265 {$label} - Currently {$currentCodec}");

                    if ($dryRun) {
                        $changed++;
                        continue;
                    }

                    // Replace codec to H.264
                    $newXml = preg_replace(
                        '/<videoCodecType>.*?<\/videoCodecType>/',
                        '<videoCodecType>H.264</videoCodecType>',
                        $xml
                    );

                    // Also downgrade profile if needed (Main is safe for H.264)
                    $newXml = preg_replace(
                        '/<H265Profile>.*?<\/H265Profile>/',
                        '',
                        $newXml
                    );

                    // PUT updated config
                    $putHttp = Http::timeout(10)
                        ->withHeaders(['Content-Type' => 'application/xml']);

                    if (! empty($camera->username)) {
                        $putHttp = $putHttp->withDigestAuth($camera->username, $camera->password ?? '');
                    }

                    $putResponse = $putHttp->send('PUT', $url, ['body' => $newXml]);

                    if ($putResponse->successful()) {
                        $this->info("  DONE {$label} - Switched to H.264");
                        $changed++;
                    } else {
                        $this->error("  FAIL {$label} - PUT returned {$putResponse->status()}");
                        $failed++;
                    }
                } else {
                    $this->error("  FAIL {$label} - Could not parse videoCodecType from XML");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("  FAIL {$label} - {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Already H.264: {$alreadyH264}");
        $this->line("  Changed to H.264: {$changed}" . ($dryRun ? ' (dry run)' : ''));
        $this->line("  Failed: {$failed}");
    }
}
