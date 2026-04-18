<?php

namespace App\Http\Middleware;

use App\Models\Anomaly;
use App\Services\AuditoriumAccessService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly AuditoriumAccessService $auditoriumAccessService,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'turnServers' => array_values(array_filter([
                config('services.turn.url') ? [
                    'urls' => config('services.turn.url'),
                    'username' => config('services.turn.username'),
                    'credential' => config('services.turn.credential'),
                ] : null,
            ])),
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'notifications' => fn () => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'recent' => $request->user()->notifications()
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn ($notification) => [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'read_at' => $notification->read_at?->toIso8601String(),
                        'created_at' => $notification->created_at?->toIso8601String(),
                        'data' => $notification->data,
                    ])
                    ->values(),
            ] : [
                'unread_count' => 0,
                'recent' => [],
            ],
            'anomalyAlerts' => fn () => $request->user() ? [
                'open_count' => $this->auditoriumAccessService
                    ->visibleAnomaliesQuery($request->user())
                    ->where('status', Anomaly::STATUS_OPEN)
                    ->count(),
                'recent' => $this->auditoriumAccessService
                    ->visibleAnomaliesQuery($request->user())
                    ->where('status', Anomaly::STATUS_OPEN)
                    ->latest('detected_at')
                    ->limit(6)
                    ->get()
                    ->map(fn (Anomaly $anomaly) => [
                        'id' => $anomaly->id,
                        'type' => $anomaly->type,
                        'detected_at' => $anomaly->detected_at?->toIso8601String(),
                        'auditorium_name' => $anomaly->auditorium?->name,
                        'building_name' => $anomaly->auditorium?->building['name'] ?? null,
                        'url' => route('anomalies.show', $anomaly),
                    ])
                    ->values(),
            ] : [
                'open_count' => 0,
                'recent' => [],
            ],
        ];
    }
}
