<?php

declare(strict_types=1);

namespace App\Classes\Cipher;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\HigherOrderTapProxy;

abstract class CipherRequest extends PendingRequest
{
    /**
     * The HTTP request timeout in seconds.
     * Signing operations can sometimes be slow.
     */
    public const int TIMEOUT = 60;

    public function __construct(?Factory $factory = null)
    {
        parent::__construct($factory);

        $this->baseUrl(config('cipher.api.domain'))
            ->timeout(self::TIMEOUT)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Overrides the HTTP Client Request method to return a custom response object.
     * This follows the pattern established by EHealthRequest.
     */
    protected function newResponse($response): HigherOrderTapProxy|CipherResponse
    {
        return tap(new CipherResponse($response), function (CipherResponse $cipherResponse) {
            // This closure can be used for future enhancements,
            // such as advanced exception handling or response logging.
        });
    }
}
