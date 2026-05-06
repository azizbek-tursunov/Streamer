<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Services\MediaMtxService;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use RuntimeException;

class YoutubeStreamMonitorController extends Controller
{
    public function __construct(
        private readonly MediaMtxService $mediaMtx,
    ) {
    }

    public function index()
    {
        $mediaMtxError = null;

        try {
            $pathStates = $this->mediaMtx->listPathStates();
        } catch (RuntimeException $e) {
            $pathStates = [];
            $mediaMtxError = $e->getMessage();
        }

        $streams = Camera::query()
            ->where(function ($query) {
                $query
                    ->whereNotNull('youtube_url')
                    ->orWhere('is_streaming_to_youtube', true);
            })
            ->orderByDesc('is_streaming_to_youtube')
            ->orderBy('name')
            ->get(['id', 'name', 'ip_address', 'youtube_url', 'is_active', 'is_streaming_to_youtube'])
            ->map(function (Camera $camera) use ($pathStates) {
                $pathName = $this->mediaMtx->getPathName($camera);
                $path = $pathStates[$pathName] ?? null;
                $isReady = (bool) ($path['ready'] ?? false);
                $readerCount = count($path['readers'] ?? []);
                $status = $this->statusFor($camera, $isReady, $readerCount, $path !== null);

                return [
                    'id' => $camera->id,
                    'name' => $camera->name,
                    'ip_address' => $camera->ip_address,
                    'youtube_url' => $camera->youtube_url,
                    'is_active' => $camera->is_active,
                    'is_streaming_to_youtube' => $camera->is_streaming_to_youtube,
                    'path_name' => $pathName,
                    'status' => $status,
                    'path_ready' => $isReady,
                    'reader_count' => $readerCount,
                    'tracks' => $path['tracks'] ?? [],
                    'bytes_received' => (int) ($path['bytesReceived'] ?? 0),
                    'bytes_sent' => (int) ($path['bytesSent'] ?? 0),
                    'ready_time' => $path['readyTime'] ?? null,
                    'updated_at' => Carbon::now()->toIso8601String(),
                ];
            })
            ->values();

        $stats = [
            'total' => $streams->count(),
            'configured' => $streams->whereNotNull('youtube_url')->count(),
            'requested' => $streams->where('is_streaming_to_youtube', true)->count(),
            'pushing' => $streams->where('status', 'pushing')->count(),
            'waiting' => $streams->where('status', 'waiting')->count(),
            'attention' => $streams->whereIn('status', ['not_configured', 'camera_inactive', 'not_ready', 'not_in_mediamtx'])->count(),
        ];

        return Inertia::render('System/YoutubeStreams', [
            'streams' => $streams,
            'stats' => $stats,
            'mediaMtxError' => $mediaMtxError,
            'refreshedAt' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function statusFor(Camera $camera, bool $isReady, int $readerCount, bool $hasPath): string
    {
        if (! $camera->youtube_url) {
            return 'not_configured';
        }

        if (! $camera->is_active) {
            return 'camera_inactive';
        }

        if (! $camera->is_streaming_to_youtube) {
            return 'configured';
        }

        if (! $hasPath) {
            return 'not_in_mediamtx';
        }

        if (! $isReady) {
            return 'waiting';
        }

        return $readerCount > 0 ? 'pushing' : 'not_ready';
    }
}
