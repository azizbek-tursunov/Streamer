<?php
use App\Models\Camera;
use App\Services\MediaMtxService;

$id = 1;
$ip = '192.168.90.5';
$user = 'admin';
$pass = 'parol400';
$port = 554;

echo "Updating Camera $id...\n";

$camera = Camera::find($id);
if (!$camera) {
    echo "Camera $id not found.\n";
    exit;
}

$camera->update([
    'ip_address' => $ip,
    'username' => $user,
    'password' => $pass,
    'port' => $port,
    'is_active' => true
]);

echo "Camera updated in DB.\n";

try {
    app(MediaMtxService::class)->updatePath($camera);
    echo "Synced successfully to MediaMTX.\n";
} catch (\Exception $e) {
    echo "Error syncing to MediaMTX: " . $e->getMessage() . "\n";
}
