<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\MedicalEvents\Repository;
use App\Models\HealthcareService;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthcareServiceRepository
{
    /**
     * Store data after successful creating in EHealth.
     *
     * @param  array  $data
     * @return HealthcareService
     * @throws Throwable
     */
    public function store(array $data): HealthcareService
    {
        return DB::transaction(function () use ($data) {
            $data = $this->storeCategoryAndType($data);

            return HealthcareService::create($data);
        });
    }

    /**
     * Sync data.
     *
     * @param  array  $items
     * @return void
     * @throws Throwable
     */
    public function sync(array $items): void
    {
        DB::transaction(function () use ($items) {
            // At first save category and type, and format json fields
            $prepared = collect($items)
                ->map(fn (array $item) => $this->storeCategoryAndType($item))
                ->map(static function (array $item) {
                    $item['available_time'] = json_encode($item['available_time'], JSON_THROW_ON_ERROR);
                    $item['not_available'] = json_encode($item['not_available'], JSON_THROW_ON_ERROR);

                    return $item;
                })
                ->values()
                ->all();

            HealthcareService::upsert($prepared, 'uuid', new HealthcareService()->getFillable());
        });
    }

    /**
     * Update status of healthcare service.
     *
     * @param  string  $uuid
     * @param  string  $status
     * @return void
     */
    public function updateStatus(string $uuid, string $status): void
    {
        HealthcareService::whereUuid($uuid)->update(['status' => $status]);
    }

    /**
     * Prepares the raw data for a healthcare service update request.
     * For update, to modify only allowed 'comment', 'available_time' and 'not_available' fields.
     *
     * @param  array  $rawData  The raw data to be processed for the update request
     * @return array The processed data ready for updating a healthcare service
     */
    public function prepareRequestUpdateData(array $rawData): array
    {
        $params = [];

        if (!empty($rawData['comment'])) {
            $params['comment'] = $rawData['comment'];
        }

        if (!empty($rawData['available_time'])) {
            foreach ($rawData['available_time'] as $index => $dayTime) {
                if (!empty($dayTime['all_day'])) {
                    $rawData['available_time'][$index]['available_start_time'] = '';
                    $rawData['available_time'][$index]['available_end_time'] = '';
                }
            }

            $params['available_time'] = available_time($rawData['available_time']);
        }

        if (!empty($rawData['not_available'])) {
            $params['not_available'] = not_available($rawData['not_available']);
        }

        return $params;
    }

    /**
     * Create instance of Healthcare Service class
     *
     * @param  array  $responseData  // The data array suitable to do fill on HealthcareService Model
     * @return HealthcareService|null
     */
    public function createOrUpdate(array $responseData): HealthcareService|null
    {
        $healthcareService = HealthcareService::firstOrNew(['uuid' => $responseData['uuid']]);

        $healthcareService->fill($responseData);

        return $healthcareService;
    }

    /**
     * Store category and type in separate tables.
     *
     * @param  array  $data
     * @return array  ID of created category and type.
     */
    protected function storeCategoryAndType(array $data): array
    {
        // Save category
        $category = Repository::codeableConcept()->store($data['category']);
        $data['category_id'] = $category->id;

        // Save if type is present
        if (!empty($data['type'])) {
            $type = Repository::codeableConcept()->store($data['type']);
            $data['type_id'] = $type->id;
        }

        // Remove nested data to avoid mass assignment issues
        unset($data['category'], $data['type']);

        return $data;
    }
}
