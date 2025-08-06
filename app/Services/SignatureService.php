<?php

namespace App\Services;

use App\Classes\Cipher\Api\CertificateAuthorityApi;
use App\Classes\Cipher\Api\TicketApi;
use App\Classes\Cipher\Exceptions\CipherApiException;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SignatureService
{
    private TicketApi $ticketApi;
    private CertificateAuthorityApi $caApi;

    public function __construct(TicketApi $ticketApi, CertificateAuthorityApi $caApi)
    {
        $this->ticketApi = $ticketApi;
        $this->caApi = $caApi;
    }

    /**
     * Signs the given data array using the Cipher API orchestration flow.
     * This method is abstract and not tied to any specific data structure.
     * It now throws exceptions directly to be handled by the caller (e.g., a trait in a component).
     *
     * @param array $dataToSign The associative array of data to be signed.
     * @param string $password The password for the key file.
     * @param string $knedpId The ID of the Certificate Authority.
     * @param UploadedFile|null $keyFile The uploaded key file.
     * @param string $taxId The tax ID of the signer.
     * @param string|null $edrpou The EDRPOU of the signer, if applicable.
     * @return string The base64 encoded signed data.
     * @throws CipherApiException|\Exception When the signing process fails.
     */
    public function signData(
        array $dataToSign,
        string $password,
        string $knedpId,
        ?UploadedFile $keyFile,
        string $taxId,
        ?string $edrpou = null
    ): string {
        Log::info('SignatureService: Starting signing process.');
        $ticketUuid = null;

        try {
            // Step 1: Process the uploaded file and data.
            $base64File = $this->getBase64KepFileContent($keyFile);
            $jsonToSign = json_encode($dataToSign, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            // Step 2: Create a signing session (ticket).
            $sessionResponse = $this->ticketApi->createSession()->throw();
            $ticketUuid = $sessionResponse->getTicketUuid();
            if (!$ticketUuid) {
                // This exception will be caught by the calling trait.
                throw new CipherApiException('Failed to get ticketUuid from session response.', $sessionResponse);
            }
            Log::info('SignatureService: Session created.', ['ticket' => $ticketUuid]);

            // Step 3: Execute the sequence of API calls for signing.
            $this->ticketApi->uploadKeyFile($ticketUuid, $base64File)->throw();
            $this->verifyKey($ticketUuid, $password, $taxId, $edrpou);
            $this->ticketApi->uploadDataToSign($ticketUuid, $jsonToSign)->throw();
            $this->ticketApi->setSessionParameters($ticketUuid, $knedpId)->throw();
            $this->ticketApi->initiateSignatureCreation($ticketUuid, $password)->throw();
            $this->waitForSignature($ticketUuid);

            // Step 4: Retrieve the final signed data.
            $signedDataResponse = $this->ticketApi->getSignedData($ticketUuid)->throw();
            $signedData = $signedDataResponse->getBase64Data();

            if (!$signedData) {
                // This exception will be caught by the calling trait.
                throw new CipherApiException('Failed to get signed data from the final response.', $signedDataResponse);
            }

            Log::info('SignatureService: Signing process completed successfully.');
            return $signedData;

        } finally {
            // Step 5: Always ensure the session is deleted after the operation, even if an error occurred.
            if ($ticketUuid) {
                $this->ticketApi->deleteSession($ticketUuid);
                Log::info('SignatureService: Session deleted.', ['ticket' => $ticketUuid]);
            }
        }
    }

    /**
     * Retrieves supported certificate authorities, cached for 7 days.
     */
    public function getCertificateAuthorities(): array
    {
        return Cache::remember('knedp_certificate_authority', now()->addDays(7), function () {
            try {
                $response = $this->caApi->getSupported()->throw();
                return $response->json('ca', []);
            } catch (\Exception $e) {
                Log::error("Failed to fetch certificate authorities.", ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * A private helper to verify key details against provided data.
     */
    private function verifyKey(string $ticketUuid, string $password, string $taxId, ?string $edrpou): void
    {
        $response = $this->ticketApi->verifyKeyContainer($ticketUuid, $password)->throw();
        $keyData = $response->json();

        if (!Arr::get($keyData, 'signature.canBeUsed')) {
            throw new CipherApiException(__('validation.custom.cipher.kepNotValid'), $response);
        }
        $expirationDate = Carbon::parse(Arr::get($keyData, 'signature.certificateInfo.notAfter.value'));
        if ($expirationDate->isPast()) {
            throw new CipherApiException(__('validation.custom.cipher.kepTimeExpired'), $response);
        }
        $inKeyDrfou = Arr::get($keyData, 'signature.certificateInfo.extensionsCertificateInfo.value.personalData.value.drfou.value');
        if ($inKeyDrfou !== $taxId) {
            throw new CipherApiException(__('validation.custom.cipher.drfouDiffer'), $response);
        }
        $inKeyEdrpou = Arr::get($keyData, 'signature.certificateInfo.extensionsCertificateInfo.value.personalData.value.edrpou.value');
        if ($edrpou && !empty($inKeyEdrpou) && $inKeyEdrpou !== $edrpou) {
            throw new CipherApiException(__('validation.custom.cipher.edrpouDiffer'), $response);
        }
    }

    /**
     * A private helper to poll for the signature status.
     */
    private function waitForSignature(string $ticketUuid, int $maxRetries = 5, int $delay = 2): void
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $statusResponse = $this->ticketApi->getSignatureStatus($ticketUuid)->throw();
            if ($statusResponse->status() === 200) return;
            if ($statusResponse->status() !== 202) {
                throw new CipherApiException('Unexpected status while waiting for signature: ' . $statusResponse->status(), $statusResponse);
            }
            sleep($delay);
        }
        throw new CipherApiException('Signing process timed out.', $statusResponse ?? null);
    }

    /**
     * Processes the uploaded KEP file and returns its base64 content.
     */
    private function getBase64KepFileContent(?UploadedFile $keyFile): string
    {
        if (!$keyFile instanceof UploadedFile || !$keyFile->isValid()) {
            throw new \RuntimeException(__('Please upload a valid KEP file.'));
        }

        $fileContents = file_get_contents($keyFile->getRealPath());
        if ($fileContents === false) {
            throw new \RuntimeException(__('Could not read KEP file content.'));
        }

        return base64_encode($fileContents);
    }
}
