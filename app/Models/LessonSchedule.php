<?php

namespace App\Models;

use App\Models\Hemis\Auditorium;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSchedule extends Model
{
    protected $fillable = [
        'hemis_id',
        'auditorium_code',
        'lesson_date',
        'subject_name',
        'employee_name',
        'group_name',
        'training_type_name',
        'lesson_pair_name',
        'start_time',
        'end_time',
        'start_timestamp',
        'end_timestamp',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'start_timestamp' => 'datetime',
            'end_timestamp' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function auditorium(): BelongsTo
    {
        return $this->belongsTo(Auditorium::class, 'auditorium_code', 'code');
    }
}
