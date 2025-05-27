<?php

namespace App\Services;

interface SignableRequestInterface
{
    public function getDataForSigning(): array;
    public function requiresSchemaNormalization(): bool;
    public function getNormalizationMethod(): ?string;
    public function getApiService(): object;
    public function sendSignedData(array $base64Data): array;
}
