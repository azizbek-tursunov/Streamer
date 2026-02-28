<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCameraRequest;
use App\Http\Requests\UpdateCameraRequest;
use App\Models\Camera;
use App\Models\Faculty;
use App\Services\MediaMtxService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CameraController extends Controller
{
    protected MediaMtxService $mediaMtx;

    public function __construct(MediaMtxService $mediaMtx)
    {
        $this->mediaMtx = $mediaMtx;
    }

    public function index(Request $request)
    {
        $query = Camera::with(['faculty']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('ip_address', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('active') && $request->active !== null) {
            $query->where('is_active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->faculty_id) {
            $query->where('faculty_id', $request->faculty_id);
        }

        return Inertia::render('Cameras/Index', [
            'cameras' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'active', 'faculty_id']),
            'faculties' => Faculty::all(),
        ]);
    }

    public function grid(Request $request)
    {
        $paginator = Camera::with(['faculty'])
            ->where('is_active', true)
            ->paginate(12)
            ->withQueryString();

        $paginator->getCollection()->transform(function ($camera) {
            // Find latest snapshot
            $files = glob(storage_path("app/public/snapshots/camera_{$camera->id}_*.jpg"));
            $latestFile = null;

            if (! empty($files)) {
                // Sort by name desc (timestamp is in name) or filemtime
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $latestFile = $files[0];
            }

            $camera->snapshot_url = $latestFile
                ? asset('storage/snapshots/'.basename($latestFile))
                : null; // Or a placeholder URL

            return $camera;
        });

        return Inertia::render('Cameras/Grid', [
            'cameras' => $paginator,
        ]);
    }

    /**
     * Return JSON with latest snapshot info for smart polling.
     * Frontend calls this every 30 seconds to check for new snapshots.
     */
    public function snapshots()
    {
        $cameras = Camera::where('is_active', true)->get();

        $snapshots = [];
        foreach ($cameras as $camera) {
            $files = glob(storage_path("app/public/snapshots/camera_{$camera->id}_*.jpg"));

            if (! empty($files)) {
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $latestFile = $files[0];
                $snapshots[$camera->id] = [
                    'url' => asset('storage/snapshots/'.basename($latestFile)),
                    'timestamp' => filemtime($latestFile),
                ];
            } else {
                $snapshots[$camera->id] = null;
            }
        }

        return response()->json($snapshots);
    }

    public function store(StoreCameraRequest $request)
    {
        $camera = Camera::create($request->validated());

        if ($camera->is_active) {
            try {
                $this->mediaMtx->addPath($camera);
            } catch (\Exception $e) {
                return back()->with('error', 'Kamera saqlandi, lekin MediaMTXga ulanmadi: '.$e->getMessage());
            }
        }

        return back()->with('success', 'Kamera muvaffaqiyatli yaratildi.');
    }

    public function show(Camera $camera)
    {
        return Inertia::render('Cameras/Show', [
            'camera' => $camera,
            'faculties' => Faculty::all(),
        ]);
    }

    public function update(UpdateCameraRequest $request, Camera $camera)
    {
        $camera->update($request->validated());

        // Sync with MediaMTX
        try {
            if ($camera->is_active) {
                $this->mediaMtx->updatePath($camera);
            } else {
                $this->mediaMtx->removePath($camera);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Kamera yangilandi, lekin MediaMTX sinxronizatsiyasi xato berdi: '.$e->getMessage());
        }

        return back()->with('success', 'Kamera muvaffaqiyatli yangilandi.');
    }

    public function destroy(Camera $camera)
    {
        try {
            $this->mediaMtx->removePath($camera);
        } catch (\Exception $e) {
            // ignore if not found
        }

        $camera->delete();

        return redirect()->route('cameras.index')->with('success', 'Kamera muvaffaqiyatli o\'chirildi.');
    }

    public function startStream(Camera $camera)
    {
        if (! $camera->youtube_url) {
            return back()->with('error', 'YouTube URL ko\'rsatilmagan.');
        }

        $camera->update(['is_streaming_to_youtube' => true]);

        try {
            $this->mediaMtx->updatePath($camera);
        } catch (\Exception $e) {
            $camera->update(['is_streaming_to_youtube' => false]);

            return back()->with('error', 'Efirni boshlashda xatolik: '.$e->getMessage());
        }

        return back()->with('success', 'YouTube efiri boshlandi.');
    }

    public function stopStream(Camera $camera)
    {
        $camera->update(['is_streaming_to_youtube' => false]);

        try {
            $this->mediaMtx->updatePath($camera);
        } catch (\Exception $e) {
            return back()->with('error', 'Efirni to\'xtatishda xatolik: '.$e->getMessage());
        }

        return back()->with('success', 'YouTube efiri to\'xtatildi.');
    }

    public function toggleActive(Camera $camera)
    {
        $camera->update(['is_active' => ! $camera->is_active]);

        try {
            if ($camera->is_active) {
                $this->mediaMtx->addPath($camera);
            } else {
                $this->mediaMtx->removePath($camera);
            }
        } catch (\Exception $e) {
            // Revert on failure
            $camera->update(['is_active' => ! $camera->is_active]);

            return back()->with('error', 'Kamera holatini o\'zgartirishda xatolik: '.$e->getMessage());
        }

        return back()->with('success', 'Kamera holati yangilandi.');
    }

    public function togglePublic(Camera $camera)
    {
        $camera->update(['is_public' => ! $camera->is_public]);

        return back()->with('success', 'Kamera ommaviy ruxsati yangilandi.');
    }

    public function updateYoutube(Request $request, Camera $camera): RedirectResponse
    {
        $validated = $request->validate([
            'youtube_url' => 'nullable|url',
            'is_streaming_to_youtube' => 'boolean',
        ]);

        $camera->update($validated);

        if ($validated['is_streaming_to_youtube']) {
            $this->mediaMtxApi->startYoutubeRestream($camera);
        } else {
            $this->mediaMtxApi->stopYoutubeRestream($camera);
        }

        return back()->with('success', 'YouTube sozlamalari saqlandi');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\CamerasImport, $request->file('file'));
            return back()->with('success', 'Kameralar muvaffaqiyatli yuklandi!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Qator {$failure->row()}: {$failure->errors()[0]}";
            }
            return back()->withErrors(['file' => implode(', ', $errors)]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Faylni o\'qishda xatolik yuz berdi: ' . $e->getMessage()]);
        }
    }
}
