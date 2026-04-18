<?php

namespace App\Services;

use App\Models\Anomaly;
use App\Models\Hemis\Auditorium;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuditoriumAccessService
{
    public function visibleAuditoriumsQuery(?User $user): Builder
    {
        $query = Auditorium::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('deans')) {
            if ($user->faculty_id) {
                $query->whereHas('faculties', fn ($facultyQuery) => $facultyQuery->where('faculties.id', $user->faculty_id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! $user->can('manage-auditorium-cameras')) {
            $query->whereNotNull('camera_id');
        }

        return $query;
    }

    public function visibleAnomaliesQuery(?User $user): Builder
    {
        $query = Anomaly::query()->with(['auditorium', 'camera', 'lessonSchedule', 'auditorium.faculties']);

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('deans')) {
            if ($user->faculty_id) {
                $query->whereHas('auditorium.faculties', fn ($facultyQuery) => $facultyQuery->where('faculties.id', $user->faculty_id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! $user->can('manage-auditorium-cameras')) {
            $query->whereHas('auditorium', fn ($auditoriumQuery) => $auditoriumQuery->whereNotNull('camera_id'));
        }

        return $query;
    }
}
