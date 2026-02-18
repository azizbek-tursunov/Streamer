<?php

namespace App\Services\HemisIntegrations;

use App\Models\Faculty;
use App\Models\Hemis\Auditorium;
use Illuminate\Support\Facades\Http;

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
            ->get("{$this->baseUrl}/data/department-list", $params);

        $response->throw();

        return $response->json();
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
}
