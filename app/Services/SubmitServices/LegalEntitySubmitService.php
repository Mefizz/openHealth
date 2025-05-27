<?php

namespace App\Services\SubmitServices;

use App\Services\SignableRequestContract;

class LegalEntitySubmitService implements SignableRequestContract
{

    public function getDataForSigning(): array
    {
        // TODO: Implement getDataForSigning() method.
    }

    public function getTaxId(): string
    {
        // TODO: Implement getTaxId() method.
    }

    public function getCipherInitiator(): string
    {
        // TODO: Implement getCipherInitiator() method.
    }

    public function sendSignedRequest(string $base64EncryptedData): array
    {
        // TODO: Implement sendSignedRequest() method.
    }

    public function handleSignedResponse(array $response): void
    {
        // TODO: Implement handleSignedResponse() method.
    }
}
