<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Services\MediaMtxService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CameraController extends Controller
{
    protected MediaMtxService $mediaMtx;

    public function __construct(MediaMtxService $mediaMtx)
    {
        $this->mediaMtx = $mediaMtx;
    }

    public function index()
    {
        return Inertia::render('Cameras/Index', [
            'cameras' => Camera::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Cameras/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'ip_address' => 'required|string',
            'port' => 'required|integer',
            'stream_path' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        // Ensure stream_path has default if empty logic or rely on model default if omitted?
        // validated data passed to create doesn't include defaults unless we merge.
        if (empty($validated['stream_path'])) {
             $validated['stream_path'] = '/';
        }

        $camera = Camera::create($validated);

        if ($camera->is_active) {
            try {
                $this->mediaMtx->addPath($camera);
            } catch (\Exception $e) {
                return redirect()->route('cameras.index')->with('error', 'Camera saved but failed to register in MediaMTX: ' . $e->getMessage());
            }
        }

        return redirect()->route('cameras.index')->with('success', 'Camera created successfully.');
    }

    public function show(Camera $camera)
    {
        return Inertia::render('Cameras/Show', [
            'camera' => $camera,
        ]);
    }

    public function edit(Camera $camera)
    {
        return Inertia::render('Cameras/Form', [
            'camera' => $camera,
        ]);
    }

    public function update(Request $request, Camera $camera)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'ip_address' => 'required|string',
            'port' => 'required|integer',
            'stream_path' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        if (empty($validated['stream_path'])) {
             $validated['stream_path'] = '/';
        }

        $camera->update($validated);
        
        // Sync with MediaMTX
        try {
            if ($camera->is_active) {
                $this->mediaMtx->updatePath($camera);
            } else {
                $this->mediaMtx->removePath($camera);
            }
        } catch (\Exception $e) {
             return redirect()->route('cameras.index')->with('error', 'Camera updated but MediaMTX sync failed: ' . $e->getMessage());
        }

        return redirect()->route('cameras.index')->with('success', 'Camera updated successfully.');
    }

    public function destroy(Camera $camera)
    {
        try {
            $this->mediaMtx->removePath($camera);
        } catch (\Exception $e) {
            // ignore if not found
        }
        
        $camera->delete();

        return redirect()->route('cameras.index')->with('success', 'Camera deleted successfully.');
    }

    public function startStream(Camera $camera)
    {
        if (!$camera->youtube_url) {
            return back()->with('error', 'No YouTube URL provided.');
        }

        $camera->update(['is_streaming_to_youtube' => true]);

        try {
            $this->mediaMtx->updatePath($camera);
        } catch (\Exception $e) {
             $camera->update(['is_streaming_to_youtube' => false]);
             return back()->with('error', 'Failed to start stream: ' . $e->getMessage());
        }

        return back()->with('success', 'Streaming to YouTube started.');
    }

    public function stopStream(Camera $camera)
    {
        $camera->update(['is_streaming_to_youtube' => false]);

        try {
            $this->mediaMtx->updatePath($camera);
        } catch (\Exception $e) {
             return back()->with('error', 'Failed to stop stream: ' . $e->getMessage());
        }

        return back()->with('success', 'Streaming to YouTube stopped.');
    }

    public function toggleActive(Camera $camera)
    {
        $camera->update(['is_active' => !$camera->is_active]);

        try {
            if ($camera->is_active) {
                $this->mediaMtx->addPath($camera);
            } else {
                $this->mediaMtx->removePath($camera);
            }
        } catch (\Exception $e) {
            // Revert on failure
             $camera->update(['is_active' => !$camera->is_active]);
             return back()->with('error', 'Failed to toggle camera status: ' . $e->getMessage());
        }

        return back()->with('success', 'Camera status updated.');
    }
}
