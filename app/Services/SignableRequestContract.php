<?php

namespace App\Services;

interface SignableRequestContract
{
    public function getDataForSigning(): array;
    public function getTaxId(): string;
    public function getCipherInitiator(): string;
    public function sendSignedRequest(string $base64EncryptedData): array;
    public function handleSignedResponse(array $response): void;
}
