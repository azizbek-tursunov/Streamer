<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($request) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'is_current' => $session->id === $request->session()->getId(),
                    'device' => $this->parseUserAgent($session->user_agent ?? ''),
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'last_active_at' => Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                ];
            });

        return Inertia::render('settings/Sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        if (! $deleted) {
            return back()->with('error', 'Sessiyani o\'chirib bo\'lmadi.');
        }

        return back()->with('success', 'Sessiya muvaffaqiyatli o\'chirildi.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Barcha boshqa sessiyalar o\'chirildi.');
    }

    private function parseUserAgent(string $ua): array
    {
        $browser = 'Noma\'lum';
        $platform = 'Noma\'lum';
        $type = 'desktop';

        // Detect browser
        if (preg_match('/Edg(e|A)?\/[\d.]+/i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\/[\d.]+/i', $ua) || preg_match('/Opera/i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/YaBrowser\/[\d.]+/i', $ua)) {
            $browser = 'Yandex';
        } elseif (preg_match('/Chrome\/[\d.]+/i', $ua) && ! preg_match('/Chromium/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/i', $ua) && ! preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
        }

        // Detect platform
        if (preg_match('/Windows/i', $ua)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $ua) && ! preg_match('/Android/i', $ua)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/i', $ua)) {
            $platform = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $platform = 'iOS';
        }

        // Detect device type
        if (preg_match('/Mobile|Android.*Mobile/i', $ua)) {
            $type = 'mobile';
        } elseif (preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua)) {
            $type = 'tablet';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
            'type' => $type,
        ];
    }
}
