<?php

declare(strict_types=1);

namespace App\Classes\eHealth;

use App\Core\Arr;

class EHealthApiFormatter
{
    /**
     * Format data for eHealth API calls.
     */
    public static function format(array $data): array
    {
        return removeEmptyKeys(Arr::toSnakeCase(self::convertDates($data)));
    }

    /**
     * Recursively convert dd.mm.yyyy date fields to ISO 8601 format.
     */
    private static function convertDates(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::convertDates($value);
            } elseif (is_string($value) && self::isDateField($key) && !empty($value)) {
                $result[$key] = convertToYmd($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if field is a date field that needs conversion.
     */
    private static function isDateField(string $key): bool
    {
        $dateFields = [
            'activeTo',
            'issuedAt',
            'manufactureDate',
            'expirationDate',
            'birthDate',
            'startDate',
            'endDate',
            'expiryDate'
        ];

        return in_array($key, $dateFields, true);
    }
}
