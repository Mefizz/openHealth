<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

class EHealthErrorParserService
{
    /**
     * Parses a structured error response from the eHealth API.
     *
     * @param array $errorResponse The full 'errors' array from the API.
     * @return string A user-friendly, concatenated error message.
     */
    public function parse(array $errorResponse): string
    {
        if (empty($errorResponse['invalid']) || !is_array($errorResponse['invalid'])) {
            return $errorResponse['message'] ?? Lang::get('ehealth_errors.generic_error');
        }

        $messages = [];
        foreach ($errorResponse['invalid'] as $error) {
            $entry = Arr::get($error, 'entry');
            $description = Arr::get($error, 'rules.0.description');

            if (!$entry || !$description) continue;

            // Get the human-readable field name from our dictionary
            $fieldName = Lang::get("ehealth_errors.fields.{$entry}", str_replace('$.', '', $entry));

            // Find the most appropriate rule description
            $ruleKey = $this->findRuleKey($description);

            $messageTemplate = Lang::get("ehealth_errors.rules.{$ruleKey}", Lang::get('ehealth_errors.default_error'));

            // Construct the final message
            $messages[] = "Поле '{$fieldName}': " . str_replace(':field', $fieldName, $messageTemplate);
        }

        return implode('<br>', $messages);
    }

    /**
     * Finds the most suitable key in the lang file for a given description.
     */
    private function findRuleKey(string $description): string
    {
        $description = strtolower($description);
        $rules = Lang::get('ehealth_errors.rules');

        foreach ($rules as $key => $text) {
            if (str_contains($description, $key)) {
                return $key;
            }
        }

        return $description;
    }
}
