<?php
use App\Models\Camera;
use App\Services\MediaMtxService;

echo "Syncing cameras...\n";
$service = new MediaMtxService();
$cameras = Camera::where('is_active', true)->get();
echo "Found " . $cameras->count() . " active cameras.\n";

foreach ($cameras as $camera) {
    try {
        echo "Syncing {$camera->name}...\n";
        $service->updatePath($camera);
        echo "Synced: {$camera->name}\n";
    } catch (\Exception $e) {
        echo "Failed: {$camera->name} - {$e->getMessage()}\n";
    }
}
echo "Done.\n";
