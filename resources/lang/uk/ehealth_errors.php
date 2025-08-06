<?php

return [
    /*
    |--------------------------------------------------------------------------
    | eHealth API Error Messages
    |--------------------------------------------------------------------------
    |
    | This file contains the translations for structured errors from the
    | eHealth API, making the parsing logic clean and maintainable.
    |
    */

    // A dictionary to map technical field names to human-readable names
    'fields' => [
        '$.drfo' => 'ІПН (РНОКПП)',
        '$.edrpou' => 'ЄДРПОУ',
        '$.owner.tax_id' => 'ІПН власника',
        '$.phones[0].number' => 'Номер телефону',
        // Add other fields as you encounter them
    ],

    // A dictionary for error rule descriptions.
    // The :field placeholder will be replaced with the human-readable field name.
    'rules' => [
        'does not match the signer drfo' => 'не співпадає з даними у вашому електронному підписі.',
        'does not match the signer edrpou' => 'не співпадає з даними у вашому електронному підписі.',
        'is not a valid value' => 'має неправильний формат.',
        'must be one of' => 'має бути одним із дозволених значень.',
        'is not a valid phone number' => 'вказано у невірному форматі. Очікується формат +380XXXXXXXXX.',
        'has already been taken' => 'вже використовується в системі.',
        // Add other rules as you encounter them
    ],

    // Fallback messages
    'default_error' => 'Виникла невідома помилка валідації для поля :field.',
    'generic_error' => 'Виникла невідома помилка від eHealth. Будь ласка, зверніться до підтримки.',

];
