<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function dean(): HasOne
    {
        return $this->hasOne(User::class, 'faculty_id');
    }

    public function auditoriums(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Hemis\Auditorium::class, 'auditorium_faculty')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'hemis_id' => 'integer',
        ];
    }
}
