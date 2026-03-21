<?php

namespace App\Imports;

use App\Models\Camera;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CamerasImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $name = $row['name'] ?? $row['nomi'] ?? null;

        // Skip row if no name is provided
        if (empty($name)) {
            return null;
        }

        $ipAddress = $row['ip_address'] ?? $row['ip'] ?? $row['ip_manzil'] ?? null;
        $port = (int) ($row['port'] ?? 554);
        $streamPath = $row['stream_path'] ?? $row['path'] ?? $row['oqim_yoli'] ?? '/Streaming/Channels/101';

        // Validate IP address format
        if ($ipAddress && ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Validate port range
        if ($port < 1 || $port > 65535) {
            return null;
        }

        // Sanitize stream_path — only allow safe URL path characters
        $streamPath = preg_replace('/[^a-zA-Z0-9\/\-_.]/', '', $streamPath);

        return new Camera([
            'name'          => mb_substr($name, 0, 255),
            'ip_address'    => $ipAddress,
            'port'          => $port,
            'username'      => mb_substr($row['username'] ?? $row['foydalanuvchi'] ?? '', 0, 255),
            'password'      => (string) mb_substr($row['password'] ?? $row['parol'] ?? '', 0, 255),
            'stream_path'   => $streamPath,
            'is_active'     => isset($row['is_active']) ? (bool) $row['is_active'] : true,
            'is_public'     => isset($row['is_public']) ? (bool) $row['is_public'] : false,
        ]);
    }
}
