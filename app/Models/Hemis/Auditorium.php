<?php

namespace App\Models\Hemis;

use Illuminate\Database\Eloquent\Model;

class Auditorium extends Model
{
    protected $table = 'auditoriums';

    protected $appends = ['auditoriumType', 'building'];

    protected $hidden = [
        'auditorium_type_code',
        'auditorium_type_name',
        'building_id',
        'building_name',
    ];

    protected $fillable = [
        'code',
        'name',
        'auditorium_type_code',
        'auditorium_type_name',
        'building_id',
        'building_name',
        'volume',
        'active',
        'camera_id',
        'sort_order',
        'building_sort_order',
    ];

    public function camera()
    {
        return $this->belongsTo(\App\Models\Camera::class);
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public function getAuditoriumTypeAttribute(): ?array
    {
        if (! $this->auditorium_type_code) {
            return null;
        }

        return [
            'code' => $this->auditorium_type_code,
            'name' => $this->auditorium_type_name,
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    public function getBuildingAttribute(): ?array
    {
        if (! $this->building_id) {
            return null;
        }

        return [
            'id' => $this->building_id,
            'name' => $this->building_name,
        ];
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'volume' => 'integer',
            'code' => 'integer',
            'building_id' => 'integer',
            'camera_id' => 'integer',
            'sort_order' => 'integer',
            'building_sort_order' => 'integer',
        ];
    }
}
