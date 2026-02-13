<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'username',
        'password',
        'ip_address',
        'port',
        'stream_path',
        'youtube_url',
        'is_active',
        'is_streaming_to_youtube',
        'branch_id',
        'floor_id',
        'faculty_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_streaming_to_youtube' => 'boolean',
    ];

    protected $appends = ['rtsp_url'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function getRtspUrlAttribute(): string
    {
        $auth = '';
        if ($this->username) {
            $auth = $this->username;
            if ($this->password) {
                $auth .= ":{$this->password}";
            }
            $auth .= '@';
        }

        $path = ltrim($this->stream_path, '/');

        return "rtsp://{$auth}{$this->ip_address}:{$this->port}/{$path}";
    }
}
