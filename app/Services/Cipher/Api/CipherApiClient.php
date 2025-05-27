<?php

namespace App\Services\Cipher\Api;

use App\Services\Cipher\Exceptions\CipherException;
use Illuminate\Support\Facades\Http;

class CipherApiClient
{
    public const SIGNATORY_INITIATOR_PERSON = 'PERSON';
    public const SIGNATORY_INITIATOR_COMPANY = 'COMPANY';

    public function sendSession(
        string $data,
        string $password,
        string $base64KeyContainer,
        string $knedp,
        string $signatoryInitiator,
        string $taxId
    ): array {
        $response = Http::post(config('cipher.endpoints.session'), [
            'data' => $data,
            'password' => $password,
            'keyContainer' => $base64KeyContainer,
            'knedp' => $knedp,
            'signatoryInitiator' => $signatoryInitiator,
            'initiatorTaxId' => $taxId,
        ]);

        if ($response->failed()) {
            throw new CipherException('Помилка при надсиланні запиту: ' . $response->body());
        }

        return $response->json();
    }

    public function getCertificateAuthorities(): array
    {
        $response = Http::get(config('cipher.endpoints.certificate_authority'));

        if ($response->failed()) {
            throw new CipherException('Помилка при отриманні сертифікатів КНЕДП: ' . $response->body());
        }

        return $response->json();
    }
}
