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
     * Display the HEMIS Auth settings configuration page.
     */
    public function hemisAuth()
    {
        return Inertia::render('HemisAuth', [
            'clientId' => Setting::get('hemis.oauth.client_id', ''),
            'clientSecret' => Setting::get('hemis.oauth.client_secret', ''),
            'urlAuthorize' => Setting::get('hemis.oauth.url_authorize', 'https://hemis.namdu.uz/oauth/authorize'),
            'urlAccessToken' => Setting::get('hemis.oauth.url_access_token', 'https://hemis.namdu.uz/oauth/access-token'),
            'urlUserInfo' => Setting::get('hemis.oauth.url_user_info', 'https://hemis.namdu.uz/oauth/api/user'),
            'redirectUri' => Setting::get('hemis.oauth.redirect_uri', route('hemis.callback.employee')),
        ]);
    }

    /**
     * Update the HEMIS Auth settings.
     */
    public function updateHemisAuth(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'url_authorize' => ['required', 'url'],
            'url_access_token' => ['required', 'url'],
            'url_user_info' => ['required', 'url'],
            'redirect_uri' => ['required', 'url'],
        ]);

        Setting::set('hemis.oauth.client_id', $validated['client_id']);
        Setting::set('hemis.oauth.client_secret', $validated['client_secret']);
        Setting::set('hemis.oauth.url_authorize', $validated['url_authorize']);
        Setting::set('hemis.oauth.url_access_token', $validated['url_access_token']);
        Setting::set('hemis.oauth.url_user_info', $validated['url_user_info']);
        Setting::set('hemis.oauth.redirect_uri', $validated['redirect_uri']);

        return back()->with('success', 'HEMIS Avtorizatsiya sozlamalari muvaffaqiyatli saqlandi.');
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
