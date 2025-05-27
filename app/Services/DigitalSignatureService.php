<?php

namespace App\Services;

use App\Classes\Cipher\Api\CipherApi;
use App\Exceptions\DigitalSignatureException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DigitalSignatureService
{
    public function __construct(
        private readonly CipherApi $cipherApi
    ) {}

    public function sign(SignableRequestInterface $signable, string $taxId, string $signatoryType = CipherApi::SIGNATORY_INITIATOR_BUSINESS): SignatureResultDto
    {
        // 1. Отримати дані з форми/сервісу/DTO
        $rawData = $signable->getDataForSigning();

        // 2. (Необов'язково) нормалізувати через schemaService
        if ($signable->requiresSchemaNormalization()) {
            $rawData = schemaService()
                ->setDataSchema($rawData, $signable->getApiService())
                ->requestSchemaNormalize($signable->getNormalizationMethod())
                ->getNormalizedData();
        }

        // 3. Зашифрувати дані
        $base64Data = CipherApi::encryptData($rawData, $taxId, $signatoryType);

        if (isset($base64Data['errors'])) {
            throw new DigitalSignatureException('Помилка шифрування', $base64Data['errors']);
        }

        // 4. Надіслати підписані дані на API
        $apiResponse = $signable->sendSignedData($base64Data);

        if (isset($apiResponse['errors'])) {
            throw new DigitalSignatureException('Помилка від API', $apiResponse['errors']);
        }

        // 5. Обробити відповідь
        return new SignatureResultDto($apiResponse, $rawData);
    }

    public function getCertificateAuthorities(): array
    {
        return $this->cipherApi->getSupportedCertificateAuthorities();
    }

    protected function validateInput(
        string $knedp,
        UploadedFile $keyContainer,
        string $password
    ): void {
        if (empty($knedp)) {
            throw new DigitalSignatureException('Certificate authority is required');
        }

        if (!$keyContainer->isValid()) {
            throw new DigitalSignatureException('Invalid key container file');
        }

        if (empty($password)) {
            throw new DigitalSignatureException('Password is required');
        }
    }

    protected function convertToBase64(UploadedFile $file): string
    {
        $path = $file->store('temp');
        $content = file_get_contents(storage_path('app/'.$path));
        Storage::delete($path);

        return base64_encode($content);
    }
}
