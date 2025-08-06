<?php

namespace App\Livewire\LegalEntity\Traits;

use App\Classes\Cipher\Exceptions\CipherApiException;
use App\Exceptions\EHealthValidationException;
use App\Services\EHealthErrorParserService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait ManagesLegalEntitySubmission
{
    /**
     * The main orchestration method for signing and submitting the legal entity.
     * It is intended to be called from the main component's action methods.
     *
     * @return array|null The validated response on success, or null on failure.
     */
    protected function signAndSubmit(): ?array
    {
        try {
            // 1. Validate KEP inputs using rules from the Cipher trait.
            $this->validate($this->rules());

            // 2. Prepare data from the component's form.
            $dataToSign = $this->prepareDataForRequest($this->legalEntityForm->toArray());
            $taxId = $this->legalEntityForm->owner['taxId'];
            $edrpou = $dataToSign['edrpou'] ?? null;

            // 3. Sign the data using the SignatureService.
            // We assume a global helper signatureService() exists, like in your example.
            $signedPayload = signatureService()->signData(
                $dataToSign,
                $this->password,
                $this->knedp,
                $this->keyContainerUpload,
                $taxId,
                $edrpou
            );

            // 4. Send the signed payload to eHealth.
            $eHealthResponse = $this->sendToEhealth($signedPayload);

            // 5. Validate the successful eHealth response.
            $validatedResponse = $this->validateResponse($eHealthResponse);
            if (empty($validatedResponse)) {
                throw new RuntimeException('Received an invalid or empty response from the eHealth server. Please contact support.');
            }

            Log::info('Legal Entity submission process completed successfully.', ['request' => $dataToSign]);

            return ['response' => $validatedResponse, 'request' => $dataToSign];

        } catch (Exception $e) {
            // Use a centralized exception handler, just like in your ManagesEmployeeForm trait.
            $this->handleSubmissionException($e);
            return null;
        }
    }

    /**
     * Sends the signed payload to the eHealth API and handles its errors.
     *
     * @param string $signedPayload
     * @return array
     * @throws EHealthValidationException|Exception
     */
    private function sendToEhealth(string $signedPayload): array
    {
        $apiPayload = ['signed_legal_entity_request' => $signedPayload, 'signed_content_encoding' => 'base64'];
        $eHealthResponse = \App\Livewire\LegalEntity\Forms\LegalEntitiesRequestApi::_createOrUpdate($apiPayload);

        if (isset($eHealthResponse['errors']) && is_array($eHealthResponse['errors'])) {
            $parser = app(EHealthErrorParserService::class);
            $userFriendlyError = $parser->parse($eHealthResponse['errors']);
            Log::warning('eHealth API returned a validation error.', ['response' => $eHealthResponse]);
            throw new EHealthValidationException($userFriendlyError);
        }

        return $eHealthResponse;
    }

    private function handleSubmissionException(Exception $e): void
    {
        // ValidationException is for FORM fields (e.g., password field is empty).
        if ($e instanceof ValidationException) {
            Log::warning('KEP input validation failed.', ['errors' => $e->errors()]);
            return; // Livewire will show these errors next to the fields.
        }

        // CipherApiException and EHealthValidationException are for API operational errors.
        // We want to show these as a global alert.
        if ($e instanceof EHealthValidationException || $e instanceof CipherApiException) {
            $userMessage = $e->getMessage();
        } else {
            // Any other exception is an unexpected system error.
            $userMessage = 'An unexpected system error occurred. Please try again.';
        }

        Log::error('Legal Entity submission failed.', [
            'error_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $this->showAlert($userMessage, 'error');
    }
}
