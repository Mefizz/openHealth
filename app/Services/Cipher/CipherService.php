<?php

namespace App\Services\Cipher;

use App\Classes\Cipher\Api\CipherApi;
use App\Services\Cipher\DTO\CipherPayload;

class CipherService
{
    public function signAndSend(CipherPayload $payload, string $taxId, string $signatory = 'PERSON'): string|array
    {
        $base64Key = $payload->convertKeyToBase64();

        return (new CipherApi())->sendSession(
            json_encode($payload->data),
            $payload->password,
            $base64Key,
            $payload->knedp,
            $signatory,
            $taxId
        );
    }

    public function getCertificateAuthorities(): array
    {
        return (new CertificateAuthorityFetcher())->get();
    }
}
