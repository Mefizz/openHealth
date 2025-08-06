<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cipher Service Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        /**
         * The base URL for the Cipher Service API.
         * The key here 'domain' is used internally by our CipherRequest,
         * but its value is taken from the CIPHER_API_URL environment variable.
         */
        'domain' => env('CIPHER_API_URL'),
    ],

];
