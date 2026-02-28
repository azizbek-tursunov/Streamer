<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id',
        'people_count',
        'snapshot_path',
        'counted_at',
    ];

    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
            'people_count' => 'integer',
        ];
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}
