<?php

namespace App\Http\Controllers;

use App\Jobs\ScanNetworkCameras;
use Illuminate\Support\Facades\Cache;
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
}
