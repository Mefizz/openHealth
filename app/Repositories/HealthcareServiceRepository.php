<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\MedicalEvents\Repository;
use App\Models\HealthcareService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
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
     * Update existed record with EHealth data.
     *
     * @param  array  $data
     * @param  bool  $updateCategoryAndType
     * @return HealthcareService
     * @throws Throwable
     */
    public function update(array $data, bool $updateCategoryAndType = true): HealthcareService
    {
        return DB::transaction(function () use ($data, $updateCategoryAndType) {
            if (empty($data['id'])) {
                throw new InvalidArgumentException('HealthcareService ID is required for update.');
            }

            if ($updateCategoryAndType) {
                $service = HealthcareService::with(['category.coding', 'type.coding'])->findOrFail($data['id']);

                $data = $this->updateCategoryAndType($service, $data);
            } else {
                $service = HealthcareService::findOrFail($data['id']);
            }

            $service->update($data);

            return $service;
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
     * Store category and type in separate tables.
     *
     * @param  array  $data
     * @return array ID of created category and type.
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

    /**
     * Update category and type from edit form.
     *
     * @param  HealthcareService  $service
     * @param  array  $data
     * @return array
     */
    protected function updateCategoryAndType(HealthcareService $service, array $data): array
    {
        // Update category (it's required)
        if (!empty($data['category'])) {
            Repository::codeableConcept()->update($service->category, $data['category']);
        }

        // Handle type
        if (array_key_exists('type', $data)) {
            if (!empty($data['type'])) {
                // Update or create
                if ($service->type) {
                    Repository::codeableConcept()->update($service->type, $data['type']);
                } else {
                    $type = Repository::codeableConcept()->store($data['type']);
                    $data['type_id'] = $type->id;
                }
            } else {
                // If was presented before in draft, but then removed
                if ($service->type) {
                    // Dissociate and delete
                    $service->type()->dissociate();
                    $service->save();

                    Repository::codeableConcept()->delete($service->type);
                }

                $data['type_id'] = null;
            }
        }

        unset($data['category'], $data['type']);

        return $data;
    }
}
