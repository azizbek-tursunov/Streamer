<?php

namespace App\Models;

use App\Models\Hemis\Auditorium;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anomaly extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const TYPE_LESSON_NO_PEOPLE = 'lesson_no_people';
    public const TYPE_PEOPLE_NO_LESSON = 'people_no_lesson';
    public const TYPE_CAMERA_OFFLINE_DURING_LESSON = 'camera_offline_during_lesson';
    public const TYPE_STALE_PEOPLE_COUNT = 'stale_people_count';
    public const TYPE_STALE_SNAPSHOT = 'stale_snapshot';

    protected $fillable = [
        'type',
        'status',
        'auditorium_id',
        'camera_id',
        'lesson_schedule_id',
        'detected_at',
        'last_seen_at',
        'resolved_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function auditorium(): BelongsTo
    {
        return $this->belongsTo(Auditorium::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function lessonSchedule(): BelongsTo
    {
        return $this->belongsTo(LessonSchedule::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnomalyEvent::class)->latest();
    }
}
