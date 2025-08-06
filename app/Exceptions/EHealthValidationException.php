<?php

namespace App\Exceptions;

use Exception;

class EHealthValidationException extends Exception
{
    // This is a custom exception to clearly identify validation errors from eHealth.
    // The message will be the user-friendly string we generate.
}
