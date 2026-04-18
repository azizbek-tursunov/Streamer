<?php

namespace App\Services;

use App\Models\Anomaly;
use App\Models\Hemis\Auditorium;
use App\Models\LessonSchedule;
use App\Models\PeopleCount;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AnomalyDetectionService
{
    private const SNAPSHOT_STALE_MINUTES = 15;

    /**
     * @return array{detected:int,resolved:int}
     */
    public function syncCurrentAnomalies(): array
    {
        $now = now(config('app.timezone'));
        $this->resolveRetiredAnomalies($now);

        $auditoriums = Auditorium::query()
            ->with(['camera.media'])
            ->where('active', true)
            ->get();

        return $this->syncAuditoriums($auditoriums, $now);
    }

    /**
     * @return array{detected:int,resolved:int}
     */
    public function syncForCamera(int $cameraId): array
    {
        $now = now(config('app.timezone'));
        $this->resolveRetiredAnomalies($now);

        $auditoriums = Auditorium::query()
            ->with(['camera.media'])
            ->where('active', true)
            ->where('camera_id', $cameraId)
            ->get();

        if ($auditoriums->isEmpty()) {
            return [
                'detected' => 0,
                'resolved' => 0,
            ];
        }

        return $this->syncAuditoriums($auditoriums, $now);
    }

    /**
     * @return array{detected:int,resolved:int}
     */
    private function syncAuditoriums(Collection $auditoriums, CarbonInterface $now): array
    {
        $currentLessons = LessonSchedule::query()
            ->where('lesson_date', $now->toDateString())
            ->where('start_timestamp', '<=', $now->copy()->addMinutes(5))
            ->where('end_timestamp', '>=', $now->copy()->subMinutes(5))
            ->whereIn('auditorium_code', $auditoriums->pluck('code'))
            ->get()
            ->keyBy('auditorium_code');

        $latestPeopleCounts = PeopleCount::query()
            ->whereIn('camera_id', $auditoriums->pluck('camera_id')->filter()->unique())
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('people_counts')
                    ->groupBy('camera_id');
            })
            ->get()
            ->keyBy('camera_id');

        $activeKeys = [];
        $detected = 0;

        foreach ($auditoriums as $auditorium) {
            $lesson = $currentLessons->get($auditorium->code);
            $camera = $auditorium->camera;
            $peopleCount = $auditorium->camera_id
                ? $latestPeopleCounts->get($auditorium->camera_id)
                : null;

            $snapshotTimestamp = $camera?->latestSnapshotTimestamp();
            $snapshotFresh = $snapshotTimestamp !== null
                && $snapshotTimestamp >= $now->copy()->subMinutes(self::SNAPSHOT_STALE_MINUTES)->timestamp;

            $peopleCountFresh = $peopleCount?->counted_at !== null
                && $peopleCount->counted_at->gte($now->copy()->subMinutes(15));

            if ($lesson && $camera && ! $snapshotFresh) {
                $detected += $this->openOrRefresh(
                    $activeKeys,
                    Anomaly::TYPE_CAMERA_OFFLINE_DURING_LESSON,
                    $auditorium,
                    $lesson,
                    [
                        'lesson_name' => $lesson->subject_name,
                        'employee_name' => $lesson->employee_name,
                    ],
                    $now
                );
            }

            if ($lesson && $camera && $peopleCountFresh && (int) $peopleCount->people_count === 0) {
                $detected += $this->openOrRefresh(
                    $activeKeys,
                    Anomaly::TYPE_LESSON_NO_PEOPLE,
                    $auditorium,
                    $lesson,
                    [
                        'lesson_name' => $lesson->subject_name,
                        'employee_name' => $lesson->employee_name,
                        'people_count' => 0,
                        'counted_at' => $peopleCount->counted_at?->toIso8601String(),
                    ],
                    $now
                );
            }

            if (! $lesson && $camera && $peopleCountFresh && (int) $peopleCount->people_count > 0) {
                $detected += $this->openOrRefresh(
                    $activeKeys,
                    Anomaly::TYPE_PEOPLE_NO_LESSON,
                    $auditorium,
                    null,
                    [
                        'people_count' => $peopleCount->people_count,
                        'counted_at' => $peopleCount->counted_at?->toIso8601String(),
                    ],
                    $now
                );
            }
        }

        $resolved = $this->resolveMissingOpenAnomalies(
            $activeKeys,
            $auditoriums->pluck('id')->all(),
            $now
        );

        return [
            'detected' => $detected,
            'resolved' => $resolved,
        ];
    }

    private function resolveRetiredAnomalies(CarbonInterface $now): void
    {
        Anomaly::query()
            ->whereIn('type', [
                Anomaly::TYPE_STALE_PEOPLE_COUNT,
                Anomaly::TYPE_STALE_SNAPSHOT,
            ])
            ->where('status', Anomaly::STATUS_OPEN)
            ->update([
                'status' => Anomaly::STATUS_RESOLVED,
                'resolved_at' => $now,
                'last_seen_at' => $now,
            ]);
    }

    private function openOrRefresh(
        array &$activeKeys,
        string $type,
        Auditorium $auditorium,
        ?LessonSchedule $lesson,
        array $payload,
        CarbonInterface $now
    ): int {
        $key = $this->makeKey($type, $auditorium->id);
        $activeKeys[$key] = true;

        $anomaly = Anomaly::query()->firstOrNew([
            'type' => $type,
            'auditorium_id' => $auditorium->id,
            'status' => Anomaly::STATUS_OPEN,
        ]);

        $wasNew = ! $anomaly->exists;

        $anomaly->fill([
            'camera_id' => $auditorium->camera_id,
            'lesson_schedule_id' => $lesson?->id,
            'last_seen_at' => $now,
            'resolved_at' => null,
            'payload' => array_merge($payload, [
                'auditorium_name' => $auditorium->name,
                'building_name' => $auditorium->building['name'] ?? null,
            ]),
        ]);

        if ($wasNew) {
            $anomaly->detected_at = $now;
        }

        $anomaly->save();

        return $wasNew ? 1 : 0;
    }

    private function resolveMissingOpenAnomalies(array $activeKeys, array $auditoriumIds, CarbonInterface $now): int
    {
        $resolved = 0;

        Anomaly::query()
            ->open()
            ->whereIn('auditorium_id', $auditoriumIds)
            ->whereIn('type', [
                Anomaly::TYPE_LESSON_NO_PEOPLE,
                Anomaly::TYPE_PEOPLE_NO_LESSON,
                Anomaly::TYPE_CAMERA_OFFLINE_DURING_LESSON,
            ])
            ->get()
            ->each(function (Anomaly $anomaly) use ($activeKeys, $now, &$resolved) {
                $key = $this->makeKey($anomaly->type, $anomaly->auditorium_id);

                if (isset($activeKeys[$key])) {
                    return;
                }

                $anomaly->update([
                    'status' => Anomaly::STATUS_RESOLVED,
                    'resolved_at' => $now,
                    'last_seen_at' => $now,
                ]);

                $resolved++;
            });

        return $resolved;
    }

    private function makeKey(string $type, int $auditoriumId): string
    {
        return "{$type}:{$auditoriumId}";
    }
}
