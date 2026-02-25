<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Display the HEMIS settings configuration page.
     */
    public function hemis()
    {
        return Inertia::render('Hemis', [
            'baseUrl' => Setting::get('hemis.base_url', config('services.hemis.base_url')),
            'token' => Setting::get('hemis.token', config('services.hemis.token')),
        ]);
    }

    /**
     * Update the HEMIS settings.
     */
    public function updateHemis(Request $request)
    {
        $validated = $request->validate([
            'base_url' => ['required', 'url'],
            'token' => ['required', 'string'],
        ]);

        Setting::set('hemis.base_url', $validated['base_url']);
        Setting::set('hemis.token', $validated['token']);

        return back()->with('success', 'HEMIS sozlamalari muvaffaqiyatli saqlandi.');
    }

    /**
     * Test the HEMIS API connection using provided credentials.
     */
    public function testHemis(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'base_url' => ['required', 'url'],
            'token' => ['required', 'string'],
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($validated['token'])
                ->timeout(10)
                ->get(rtrim($validated['base_url'], '/') . '/data/department-list', [
                    'limit' => 1
                ]);

            if ($response->successful()) {
                return back()->with('success', "HEMIS API bilan muvaffaqiyatli bog'lanildi! (HTTP 200)");
            }

            return back()->with('error', "API ulanishida xatolik: HTTP " . $response->status());
        } catch (\Exception $e) {
            return back()->with('error', "Serverga ulanib bo'lmadi: " . $e->getMessage());
        }
    }
}
