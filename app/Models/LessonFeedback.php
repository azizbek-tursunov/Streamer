<?php

namespace App\Models;

use App\Models\Hemis\Auditorium;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonFeedback extends Model
{
    use HasFactory;

    protected $table = 'lesson_feedbacks';

    protected $fillable = [
        'user_id',
        'auditorium_id',
        'lesson_name',
        'employee_name',
        'group_name',
        'start_time',
        'end_time',
        'type',
        'message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditorium(): BelongsTo
    {
        return $this->belongsTo(Auditorium::class);
    }
}
