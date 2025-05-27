<?php

namespace App\Traits;

use App\Services\DictionaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

trait UsesDictionaries
{
    public array $dictionaries = [];
    public array $employeeTypePosition = [];

    protected function getDictionary(): void
    {
        $legalEntity = Auth::user()?->legalEntity;

        // якщо нема — дефолт
        $legalEntityType = $legalEntity->type ?? 'MSP';

        /** @var DictionaryService $dictionaryService */
        $dictionaryService = App::make(DictionaryService::class);

        $this->dictionaries = $dictionaryService->getDictionaries($this->dictionaryNames ?? []);

        if (in_array('EMPLOYEE_TYPE', $this->dictionaryNames ?? [])) {
            $roles = config("ehealth.legal_entity_type.{$legalEntityType}.roles", []);
            $this->dictionaries['EMPLOYEE_TYPE'] = $this->getDictionariesFields($roles, 'EMPLOYEE_TYPE');

            $this->employeeTypePosition = [];
            foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {
                $positions = config("ehealth.employee_type.{$employeeType}.position", []);
                $this->employeeTypePosition[$employeeType] = $this->getDictionariesFields($positions, 'POSITION');
            }
        }
    }

    protected function getDictionariesFields(array $keys, string $dictionary): array
    {
        $dictionaryData = $this->dictionaries[$dictionary] ?? [];
        return array_filter(
            $dictionaryData,
            fn($_, $key) => in_array($key, $keys),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
