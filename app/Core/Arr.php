<?php

namespace App\Core;

use Illuminate\Support\Str;

class Arr extends \Illuminate\Support\Arr
{
    public static function toSnakeCase(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = \Illuminate\Support\Str::snake($key);

            if (is_array($value)) {
                $result[$newKey] = self::toSnakeCase($value);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    public static function snakeKeys(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = Str::snake($key);
            $result[$newKey] = is_array($value)
                ? self::snakeKeys($value)
                : $value;
        }

        return $result;
    }

    /**
     * Recursively transform all keys to camelCase
     */
    public static function camelKeys(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = Str::camel($key);
            $result[$newKey] = is_array($value)
                ? self::camelKeys($value)
                : $value;
        }

        return $result;
    }
}
