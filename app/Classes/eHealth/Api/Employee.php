<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use App\Models\LegalEntity;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Employee extends EHealthRequest
{
    public const string URL = '/api/employees';

    /**
     * Gets a single page of employees from E-Health.
     * This method is now responsible for only one request.
     *
     * @param array $filters An associative array of query parameters to filter the results.
     * @param int   $page    The page number to fetch.
     *
     * @return array The JSON response from the API, decoded into an associative array.
     * @throws ConnectionException
     */
    public function getMany(array $filters, int $page = 1): array
    {
        $perPage = config('ehealth.api.page_size', 150);

        $queryParams = array_merge($filters, [
            'page'      => $page,
            'page_size' => $perPage
        ]);

        return $this->get(self::URL, $queryParams)->json();
    }

    /**
     * Prepares a basic data structure from the eHealth API response.
     * Uses pre-fetched maps to avoid N+1 database queries.
     *
     * @param array $ehealthData Raw data for one employee from the E-Health API.
     * @param LegalEntity $legalEntity
     * @param User|null $user
     * @param Collection $partiesMap A collection of Party models keyed by UUID.
     * @param Collection $divisionsMap A collection of Division models keyed by UUID.
     * @return array The partially prepared data.
     */
    public static function prepareEmployeeDataForDb(
        array $ehealthData,
        LegalEntity $legalEntity,
        ?User $user,
        Collection $partiesMap,
        Collection $divisionsMap
    ): array {
        $prepared = [
            'uuid' => $ehealthData['id'],
            'status' => $ehealthData['status'],
            'position' => $ehealthData['position'],
            'employee_type' => $ehealthData['employee_type'],
            'start_date' => Carbon::parse($ehealthData['start_date'])->toDateString(),
            'end_date' => isset($ehealthData['end_date']) ? Carbon::parse($ehealthData['end_date'])->toDateString() : null,
            'inserted_at' => Carbon::now(),
            'legal_entity_id' => $legalEntity->id,
            'legal_entity_uuid' => $legalEntity->uuid,
            'party_id' => null,
            'user_id' => null,
            'division_id' => null,
        ];

        $partyUuid = $ehealthData['party']['id'] ?? null;
        if ($partyUuid && $partiesMap->has($partyUuid)) {
            $party = $partiesMap->get($partyUuid);
            $prepared['party_id'] = $party->id;
            $user = $user ?? $party->user;
        }

        if ($user) {
            $prepared['user_id'] = $user->id;
        }

        $divisionUuid = $ehealthData['division']['id'] ?? null;
        if ($divisionUuid && $divisionsMap->has($divisionUuid)) {
            $division = $divisionsMap->get($divisionUuid);
            $prepared['division_id'] = $division->id;
        }

        return $prepared;
    }
}
