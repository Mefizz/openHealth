<?php

declare(strict_types=1);

namespace App\Classes\Cipher\Api;

use App\Classes\Cipher\CipherClient;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class CertificateAuthorityApi extends CipherClient
{
    /**
     * Fetches the list of supported Certificate Authorities.
     *
     * @throws ConnectionException
     */
    public function getSupported(): PromiseInterface
    {
        return $this->get('/certificateAuthority/supported');
    }
}
