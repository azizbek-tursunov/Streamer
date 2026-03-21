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
        $token = Setting::get('hemis.token', config('services.hemis.token'));

        return Inertia::render('Hemis', [
            'baseUrl' => Setting::get('hemis.base_url', config('services.hemis.base_url')),
            'tokenSet' => ! empty($token),
        ]);
    }

    /**
     * Update the HEMIS settings.
     */
    public function updateHemis(Request $request)
    {
        $validated = $request->validate([
            'base_url' => ['required', 'url'],
            'token' => ['nullable', 'string'],
        ]);

        // Prevent SSRF: only allow hemis.uz domains
        $host = parse_url($validated['base_url'], PHP_URL_HOST);
        if (! $host || ! str_ends_with($host, '.hemis.uz') && $host !== 'hemis.uz') {
            return back()->with('error', "Faqat hemis.uz domeniga ruxsat berilgan.");
        }

        Setting::set('hemis.base_url', $validated['base_url']);
        // Only update token if a new one was provided
        if (! empty($validated['token'])) {
            Setting::set('hemis.token', $validated['token']);
        }

        return back()->with('success', 'HEMIS sozlamalari muvaffaqiyatli saqlandi.');
    }

    /**
     * Display the HEMIS Auth settings configuration page.
     */
    public function hemisAuth()
    {
        $clientSecret = Setting::get('hemis.oauth.client_secret', '');

        return Inertia::render('HemisAuth', [
            'clientId' => Setting::get('hemis.oauth.client_id', ''),
            'clientSecretSet' => ! empty($clientSecret),
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
            'client_secret' => ['nullable', 'string'],
            'url_authorize' => ['required', 'url'],
            'url_access_token' => ['required', 'url'],
            'url_user_info' => ['required', 'url'],
            'redirect_uri' => ['required', 'url'],
        ]);

        Setting::set('hemis.oauth.client_id', $validated['client_id']);
        // Only update secret if a new one was provided
        if (! empty($validated['client_secret'])) {
            Setting::set('hemis.oauth.client_secret', $validated['client_secret']);
        }
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
            'token' => ['nullable', 'string'],
        ]);

        // Prevent SSRF: only allow HTTPS URLs to external hemis domains
        $host = parse_url($validated['base_url'], PHP_URL_HOST);
        if (! $host || ! str_ends_with($host, '.hemis.uz') && $host !== 'hemis.uz') {
            return back()->with('error', "Faqat hemis.uz domeniga ruxsat berilgan.");
        }

        // Use provided token or fall back to saved token
        $token = ! empty($validated['token']) ? $validated['token'] : Setting::get('hemis.token', config('services.hemis.token'));
        if (empty($token)) {
            return back()->with('error', "Token kiritilmagan va saqlangan token topilmadi.");
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->timeout(10)
                ->get(rtrim($validated['base_url'], '/') . '/data/department-list', [
                    'limit' => 1
                ]);

            if ($response->successful()) {
                return back()->with('success', "HEMIS API bilan muvaffaqiyatli bog'lanildi! (HTTP 200)");
            }

            return back()->with('error', "API ulanishida xatolik: HTTP " . $response->status());
        } catch (\Exception $e) {
            return back()->with('error', "Serverga ulanib bo'lmadi.");
        }
    }
}
