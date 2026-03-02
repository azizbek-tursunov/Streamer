<?php

namespace App\Services\HemisIntegrations;

use App\Models\Faculty;
use App\Models\Hemis\Auditorium;
use App\Models\LessonSchedule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HemisApiService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
    ) {}

    /**
     * Fetch the auditorium list from HEMIS API.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getAuditoriums(array $params = []): array
    {
        $response = Http::withToken($this->token)
            ->timeout(30)
            ->get("{$this->baseUrl}/data/auditorium-list", $params);

        $response->throw();

        return $response->json();
    }

    /**
     * Fetch the department list from HEMIS API.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getDepartments(array $params = []): array
    {
        $response = Http::withToken($this->token)
            ->timeout(30)
            ->get("{$this->baseUrl}/data/department-list", $params);

        $response->throw();

        return $response->json();
    }

    /**
     * Fetch the employee list from HEMIS API.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getEmployees(array $params = []): array
    {
        $response = Http::withToken($this->token)
            ->timeout(30)
            ->get("{$this->baseUrl}/data/employee-list", $params);

        $response->throw();

        return $response->json();
    }

    /**
     * Sync employees of given types from HEMIS API into the local users table.
     *
     * @param  int[]  $employeeTypes
     * @return int Number of employees synced
     */
    public function syncEmployees(array $employeeTypes = [11, 14]): int
    {
        $synced = 0;

        foreach ($employeeTypes as $type) {
            $page = 1;
            $limit = 200;

            do {
                $response = $this->getEmployees([
                    'type' => '',
                    '_employee_type' => $type,
                    'limit' => $limit,
                    'page' => $page,
                ]);

                $items = $response['data']['items'] ?? [];
                $pagination = $response['data']['pagination'] ?? null;

                foreach ($items as $emp) {
                    $hemisId = $emp['employee_id_number'] ?? null;
                    $fullName = $emp['full_name'] ?? trim(
                        ($emp['second_name'] ?? '') . ' ' .
                        ($emp['first_name'] ?? '') . ' ' .
                        ($emp['third_name'] ?? '')
                    );

                    if (!$hemisId) {
                        continue;
                    }

                    $userEmail = $hemisId . '@hemis.local';

                    User::updateOrCreate(
                        ['employee_id_number' => $hemisId],
                        [
                            'email' => $userEmail, // Fallback placeholder email
                            'name' => $fullName ?: 'HEMIS User',
                            'password' => bcrypt(str()->random(24)),
                        ]
                    );
                    $synced++;
                }

                $page++;
            } while ($pagination && $page <= ($pagination['pageCount'] ?? $pagination['page_count'] ?? 1));
        }

        return $synced;
    }

    /**
     * Fetch all auditoriums from HEMIS API and sync them into the local database.
     *
     * @return int Number of auditoriums synced
     */
    public function syncAuditoriums(): int
    {
        $page = 1;
        $limit = 200;
        $synced = 0;

        do {
            $response = $this->getAuditoriums([
                'page' => $page,
                'limit' => $limit,
                '_building' => '',
                '_auditorium_type' => '',
            ]);

            $items = $response['data']['items'] ?? [];
            $pagination = $response['data']['pagination'] ?? null;

            foreach ($items as $item) {
                Auditorium::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'auditorium_type_code' => $item['auditoriumType']['code'] ?? null,
                        'auditorium_type_name' => $item['auditoriumType']['name'] ?? null,
                        'building_id' => $item['building']['id'] ?? null,
                        'building_name' => $item['building']['name'] ?? null,
                        'volume' => $item['volume'] ?? 0,
                        'active' => $item['active'] ?? true,
                    ]
                );
                $synced++;
            }

            $page++;
        } while ($pagination && $page <= $pagination['pageCount']);

        return $synced;
    }

    /**
     * Fetch all faculties (structure_type=11) from HEMIS API and sync them into the local database.
     *
     * @return int Number of faculties synced
     */
    public function syncFaculties(): int
    {
        $page = 1;
        $limit = 200;
        $synced = 0;

        do {
            $response = $this->getDepartments([
                'page' => $page,
                'limit' => $limit,
                'active' => '',
                '_structure_type' => '11',
                'parent' => '',
            ]);

            $items = $response['data']['items'] ?? [];
            $pagination = $response['data']['pagination'] ?? null;

            foreach ($items as $item) {
                Faculty::updateOrCreate(
                    ['hemis_id' => $item['id']],
                    [
                        'name' => $item['name'],
                        'code' => $item['code'] ?? null,
                        'structure_type_code' => $item['structureType']['code'] ?? null,
                        'structure_type_name' => $item['structureType']['name'] ?? null,
                        'locality_type_code' => $item['localityType']['code'] ?? null,
                        'locality_type_name' => $item['localityType']['name'] ?? null,
                        'active' => $item['active'] ?? true,
                    ]
                );
                $synced++;
            }

            $page++;
        } while ($pagination && $page <= $pagination['pageCount']);

        return $synced;
    }

    /**
     * Fetch the schedule list from HEMIS API.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getSchedule(array $params = []): array
    {
        $response = Http::withToken($this->token)
            ->timeout(30)
            ->get("{$this->baseUrl}/data/schedule-list", $params);

        $response->throw();

        return $response->json();
    }

    /**
     * Sync today's lesson schedules from HEMIS API into the local database.
     *
     * @return int Number of lesson schedules synced
     */
    public function syncSchedules(): int
    {
        $timezone = config('app.timezone');
        $today = now($timezone);
        $todayDate = $today->toDateString();

        // Remove old records (before today)
        LessonSchedule::where('lesson_date', '<', $todayDate)->delete();

        $auditoriums = Auditorium::where('active', true)->get();
        $synced = 0;

        foreach ($auditoriums as $auditorium) {
            try {
                $synced += $this->syncAuditoriumSchedule($auditorium->code);
            } catch (\Exception $e) {
                Log::warning("Failed to sync schedule for auditorium {$auditorium->code}: {$e->getMessage()}");
            }
        }

        return $synced;
    }

    /**
     * Sync today's lesson schedules for a single auditorium from HEMIS API.
     *
     * @param string $auditoriumCode
     * @return int Number of lesson schedules synced
     */
    public function syncAuditoriumSchedule(string $auditoriumCode): int
    {
        $timezone = config('app.timezone');
        $today = now($timezone);
        $todayDate = $today->toDateString();
        $startOfDay = $today->copy()->startOfDay()->timestamp;
        $endOfDay = $today->copy()->endOfDay()->timestamp;

        $synced = 0;
        $page = 1;
        $limit = 200;

        do {
            $response = $this->getSchedule([
                '_auditorium' => $auditoriumCode,
                'lesson_date_from' => $startOfDay,
                'lesson_date_to' => $endOfDay,
                'limit' => $limit,
                'page' => $page,
            ]);

            $items = $response['data']['items'] ?? [];
            $pagination = $response['data']['pagination'] ?? null;

            foreach ($items as $item) {
                $date = \Carbon\Carbon::createFromTimestamp(
                    $item['lesson_date'],
                    $timezone
                )->startOfDay();

                // Skip lessons not from today
                if ($date->toDateString() !== $todayDate) {
                    continue;
                }

                $startTime = $item['lessonPair']['start_time'] ?? '00:00';
                $endTime = $item['lessonPair']['end_time'] ?? '00:00';

                [$sh, $sm] = explode(':', $startTime);
                [$eh, $em] = explode(':', $endTime);

                LessonSchedule::updateOrCreate(
                    [
                        'hemis_id' => $item['id'],
                        'lesson_date' => $date->toDateString(),
                        'auditorium_code' => $auditoriumCode,
                    ],
                    [
                        'subject_name' => $item['subject']['name'] ?? '',
                        'employee_name' => $item['employee']['name'] ?? '',
                        'group_name' => $item['group']['name'] ?? '',
                        'training_type_name' => $item['trainingType']['name'] ?? null,
                        'lesson_pair_name' => $item['lessonPair']['name'] ?? null,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'start_timestamp' => $date->copy()->setTime((int) $sh, (int) $sm),
                        'end_timestamp' => $date->copy()->setTime((int) $eh, (int) $em),
                        'raw_data' => $item,
                    ]
                );
                $synced++;
            }

            $page++;
        } while ($pagination && $page <= $pagination['pageCount']);

        return $synced;
    }
}
