<?php

namespace App\Traits;

use App\Core\Arr;
use Illuminate\Validation\ValidationException;

trait HandlesSnakeCaseForm
{
    /**
     * @throws ValidationException
     */
    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        $validated = parent::validate($rules, $messages, $attributes);
        return Arr::snakeKeys($validated);
    }

    public function validatedSnakeCase(): array
    {
        return Arr::toSnakeCase($this->validate());
    }

    /**
     * If only a specific key is needed
     */
    public function validatedFieldSnakeCase(string $key)
    {
        return data_get($this->validatedSnakeCase(), $key);
    }
}
