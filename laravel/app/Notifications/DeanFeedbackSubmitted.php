<?php

namespace App\Notifications;

use App\Models\LessonFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DeanFeedbackSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly LessonFeedback $feedback,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $facultyName = $this->feedback->faculty?->name;
        $auditoriumName = $this->feedback->auditorium?->name;
        $typeLabel = $this->feedback->type === 'good' ? 'Ijobiy' : 'Salbiy';

        return [
            'feedback_id' => $this->feedback->id,
            'faculty_id' => $this->feedback->faculty_id,
            'faculty_name' => $facultyName,
            'auditorium_name' => $auditoriumName,
            'lesson_name' => $this->feedback->lesson_name,
            'employee_name' => $this->feedback->employee_name,
            'type' => $this->feedback->type,
            'type_label' => $typeLabel,
            'message' => $this->feedback->message,
            'title' => "{$typeLabel} fikr-mulohaza",
            'body' => trim(collect([
                $facultyName,
                $auditoriumName,
                $this->feedback->lesson_name,
            ])->filter()->join(' • ')),
            'url' => '/feedbacks',
            'created_at' => $this->feedback->created_at?->toIso8601String(),
        ];
    }
}
