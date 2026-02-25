<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncHemisEmployees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Setting::set('hemis.sync.status', 'processing');
        Setting::set('hemis.sync.message', 'Sinxronizatsiya boshlandi...');
        Setting::set('hemis.sync.last_run', now()->toDateTimeString());

        $baseUrl = rtrim(Setting::get('hemis.base_url', ''), '/');
        $token = Setting::get('hemis.token');

        if (!$baseUrl || !$token) {
            $msg = 'HEMIS API sozlamalari topilmadi. Avval API kaliti va manzilni kiriting.';
            Log::error("SyncHemisEmployees: {$msg}");
            Setting::set('hemis.sync.status', 'error');
            Setting::set('hemis.sync.message', $msg);
            return;
        }

        try {
            $service = new HemisApiService($baseUrl, $token);
            $syncedCount = $service->syncEmployees([11, 14]);

            $successMsg = "HEMISdan jami {$syncedCount} ta xodim muvaffaqiyatli sinxronizatsiya qilindi.";
            Log::info("SyncHemisEmployees: {$successMsg}");
            Setting::set('hemis.sync.status', 'success');
            Setting::set('hemis.sync.message', $successMsg);
        } catch (\Exception $e) {
            $msg = 'HEMIS sinxronizatsiyada xatolik: ' . $e->getMessage();
            Log::error("SyncHemisEmployees: {$msg}");
            Setting::set('hemis.sync.status', 'error');
            Setting::set('hemis.sync.message', $msg);
        }
    }

    /**
     * Handle a job failure (e.g., TimeoutExceededException, fatal errors).
     */
    public function failed(\Throwable $exception): void
    {
        $msg = 'Queue jarayoni xatoga uchradi: ' . $exception->getMessage();
        Log::error("SyncHemisEmployees (Fatal): {$msg}");
        Setting::set('hemis.sync.status', 'error');
        Setting::set('hemis.sync.message', $msg);
    }
}
