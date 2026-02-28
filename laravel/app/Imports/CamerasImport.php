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

        return new Camera([
            'name'          => $name,
            'ip_address'    => $row['ip_address'] ?? $row['ip'] ?? $row['ip_manzil'] ?? null,
            'port'          => $row['port'] ?? 554,
            'username'      => $row['username'] ?? $row['foydalanuvchi'] ?? null,
            'password'      => (string) ($row['password'] ?? $row['parol'] ?? ''),
            'stream_path'   => $row['stream_path'] ?? $row['path'] ?? $row['oqim_yoli'] ?? '/Streaming/Channels/101',
            'is_active'     => isset($row['is_active']) ? (bool) $row['is_active'] : true,
            'is_public'     => isset($row['is_public']) ? (bool) $row['is_public'] : false,
        ]);
    }
}
