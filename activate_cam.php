<?php
use App\Models\Camera;
use App\Services\MediaMtxService;

$id = 1;
echo "Activating Camera $id...\n";

$camera = Camera::find($id);
if (!$camera) {
    echo "Camera $id not found.\n";
    exit;
}

$camera->is_active = true;
$camera->save();
echo "Camera set to active in DB.\n";

try {
    app(MediaMtxService::class)->addPath($camera);
    echo "Synced successfully to MediaMTX.\n";
} catch (\Exception $e) {
    echo "Error syncing to MediaMTX: " . $e->getMessage() . "\n";
    // We do NOT revert here for debugging purposes
}
