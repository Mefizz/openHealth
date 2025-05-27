<?php

namespace App\Services\Cipher\Exceptions;

use Exception;

class CipherException extends Exception
{
    public function report(): void
    {
        // логування у Sentry або Laravel logs
    }

    public function render($request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage(),
        ], 422);
    }

}
