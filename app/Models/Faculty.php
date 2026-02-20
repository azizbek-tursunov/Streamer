<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $fillable = [
        'name',
        'hemis_id',
        'code',
        'structure_type_code',
        'structure_type_name',
        'locality_type_code',
        'locality_type_name',
        'active',
    ];

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }

    public function auditoriums(): HasMany
    {
        return $this->hasMany(\App\Models\Hemis\Auditorium::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'hemis_id' => 'integer',
        ];
    }
}
