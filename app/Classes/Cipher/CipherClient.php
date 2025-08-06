<?php

declare(strict_types=1);

namespace App\Classes\Cipher;

/**
 * This is a concrete implementation of the abstract CipherRequest.
 * Its purpose is to be a specific, instantiable class that the
 * service container can build when CipherRequest is requested.
 */
class CipherClient extends CipherRequest
{
    // This class remains empty as it inherits all necessary logic
    // from the abstract CipherRequest class.
}
