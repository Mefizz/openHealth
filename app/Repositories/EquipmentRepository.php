<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Arr;
use App\Models\Equipment;
use InvalidArgumentException;
use Throwable;

class EquipmentRepository
{
    /**
     * Store data after successful creating in EHealth.
     *
     * @param  array  $data
     * @return Equipment
     * @throws Throwable
     */
    public function store(array $data): Equipment
    {
        $equipment = Equipment::create(Arr::except($data, ['names', 'properties']));
        $equipment->names()->createMany($data['names']);

        return $equipment;
    }

    /**
     * Update existed record with EHealth data.
     *
     * @param  array  $data
     * @return Equipment
     * @throws Throwable
     */
    public function update(array $data): Equipment
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Equipment ID is required for update.');
        }

        $equipment = Equipment::findOrFail($data['id']);

        $equipment->update(Arr::except($data, ['id', 'names', 'properties']));

        // Update names
        $equipment->names()->delete();
        $equipment->names()->createMany($data['names']);

        return $equipment;
    }
}
