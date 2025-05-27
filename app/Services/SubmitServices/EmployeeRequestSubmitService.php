<?php

namespace App\Services\SubmitServices;

use App\Classes\Cipher\Api\CipherApi;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Services\SignableRequestContract;
use App\Services\DigitalSignatureService;

class EmployeeRequestSubmitService implements SignableRequestContract
{
    public function __construct(
        protected array $employeeFormData,
        protected DigitalSignatureService $signatureService
    ) {}

    public function submit(): void
    {
        $this->signatureService->sign($this);
    }

    public function getDataForSigning(): array
    {
        return $this->employeeFormData;
    }

    public function getTaxId(): string
    {
        return $this->employeeFormData['tax_id'];
    }

    public function getCipherInitiator(): string
    {
        return CipherApi::SIGNATORY_INITIATOR_PERSONAL;
    }

    public function sendSignedRequest(string $base64EncryptedData): array
    {
        return EmployeeRequestApi::create([
            'signed_content' => $base64EncryptedData,
            'signed_content_encoding' => 'base64',
        ]);
    }

    public function handleSignedResponse(array $response): void
    {
        // handle: збереження в БД, логіка переходів, flash-повідомлення тощо
        if (!isset($response['employee_id'])) {
            throw new \RuntimeException("Employee creation failed");
        }

        // Наприклад:
        session()->flash('flashMessage', [
            'message' => 'Співробітника успішно створено',
            'type' => 'success',
        ]);
    }
}
