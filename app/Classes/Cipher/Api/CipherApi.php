<?php

namespace App\Classes\Cipher\Api;

use App\Classes\Cipher\Exceptions\ApiException;
use App\Classes\Cipher\Errors\ErrorHandler;
use App\Classes\Cipher\Request;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CipherApi
{
    const SIGNATORY_INITIATOR_BUSINESS = 'Business';
    const SIGNATORY_INITIATOR_PERSON = 'Person';

    private string $ticketUuid = '';
    private string $base64File = '';
    private string $password = '';
    private string $dataSignature;
    private string $knedp;

    /**
     * Send request to create session and subsequently upload KEYP.
     *
     * @param string $dataSignature Base64 encoded signed data.
     * @param string $password Password for KEYP creation.
     * @param string $base64File KEYP file in base64 format.
     * @param string $knedp Certificate Authority Identifier (KNEPD).
     * @param string $initiator Type of signatory (Business/Person).
     * @param string $taxId Tax ID (EDRPOU/DRFOU) for verification.
     * @return string|array Returns KEYP in base64 format or array of errors.
     */
    public function sendSession(
        string $dataSignature,
        string $password,
        string $base64File,
        string $knedp,
        string $initiator,
        string $taxId
    ): array|string
    {
        $this->dataSignature = base64_encode($dataSignature);
        $this->password = $password;
        $this->base64File = $base64File;
        $this->knedp = $knedp;

        try {
            $this->createSession();
            $this->loadData();
            $this->setSessionParameters();
            $this->uploadFile();
            $this->verifyWithFileContainer($taxId, $initiator);
            $this->createKeyp();
            $this->getKeypCreator();
            return $this->getKeyp();
        } catch (ApiException $e) {
            Log::error('CipherApi::sendSession ApiException: ' . $e->getMessage(), ['errors' => $e->getErrors()]);
            return $e->getErrors();
        } catch (\Exception $e) {
            Log::error('CipherApi::sendSession unexpected error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [
                'errors' => [
                    'general' => __('api.cipher.unexpected_error') . ': ' . $e->getMessage()
                ],
                'message' => __('api.cipher.unexpected_error_short'),
                'status' => 500
            ];
        } finally {
            if (!empty($this->ticketUuid)) {
                try {
                    $this->deleteSession();
                } catch (\Exception $e) {
                    Log::warning("Failed to delete Cipher session {$this->ticketUuid}: " . $e->getMessage());
                }
            }
        }
    }

    private function createSession(): void
    {
        $response = $this->sendRequest('post', '/ticket');
        if (empty($response['ticketUuid'])) {
            ErrorHandler::throwError(
                [
                    'message'      => __('api.cipher.create_session_failed'),
                    'failureCause' => 'Empty ticketUuid received from API',
                ]
            );
        }
        $this->ticketUuid = $response['ticketUuid'];
    }

    private function loadData(): void
    {
        $this->sendRequest('post', "/ticket/{$this->ticketUuid}/data", ['base64Data' => $this->dataSignature]);
    }

    private function setSessionParameters(): void
    {
        $params = [
            "caId" => $this->knedp,
            "cadesType" => "CADES_X_LONG",
            "signatureType" => "attached",
            'embedDataTs' => 'true'
        ];
        $this->sendRequest('put', "/ticket/{$this->ticketUuid}/option", $params);
    }

    private function uploadFile(): void
    {
        $this->sendRequest('put', "/ticket/{$this->ticketUuid}/keyStore", ['base64Data' => $this->base64File], true);
    }

    private function createKeyp(): void
    {
        $this->sendRequest('post', "/ticket/{$this->ticketUuid}/ds/creator", ['keyStorePassword' => $this->password]);
    }

    private function getKeypCreator($retryCount = 0, $maxRetries = 5)
    {
        $status = $this->sendRequest('get', "/ticket/{$this->ticketUuid}/ds/creator");

        if (isset($status['status']) && $status['status'] === 202 && $retryCount < $maxRetries) {
            sleep(2);
            return $this->getKeypCreator($retryCount + 1, $maxRetries);
        }

        if (isset($status['status']) && $status['status'] === 200) {
            return $status;
        }

        // Якщо жодна з умов не виконана після ретраїв, кидаємо помилку
        ErrorHandler::throwError(
            [
                'message'      => __('api.cipher.keyp_creation_timeout'),
                'failureCause' => 'Key creation did not complete within expected time or status is not 200/202',
                'api_response' => $status, // Додаємо відповідь API для налагодження
            ]
        );
        // Цей рядок буде досягнутий тільки якщо ErrorHandler::throwError не викинув виняток (що не повинно бути)
        return null;
    }


    private function getKeyp(): string
    {
        $response = $this->sendRequest('get', "/ticket/{$this->ticketUuid}/ds/base64Data");
        if (empty($response['base64Data'])) {
            ErrorHandler::throwError(
                [
                    'message'      => __('api.cipher.get_keyp_failed'),
                    'failureCause' => 'Empty base64Data received from API',
                ]
            );
        }
        return $response['base64Data'];
    }

    private function deleteSession(): void
    {
        $this->sendRequest('delete', "/ticket/{$this->ticketUuid}");
    }

    private function sendRequest(string $method, string $url, array $data = [], bool $isFileUpload = false)
    {
        // Передача $isFileUpload до класу Request
        return new Request($method, $url, json_encode($data), $isFileUpload)->sendRequest();
    }

    // Additional methods for decoding file container
    public function getDecodingFileContainerBase64()
    {
        return $this->sendRequest('get', "/ticket/{$this->ticketUuid}/decryptor/base64Data");
    }

    private function getDecodingFileContainerResultData($retryCount = 0, $maxRetries = 5)
    {
        $status = $this->sendRequest('get', "/ticket/{$this->ticketUuid}/decryptor");

        if ($status['status'] === 202 && $retryCount < $maxRetries) {
            return $this->getDecodingFileContainerResultData($retryCount + 1, $maxRetries);
        }

        return $status;
    }

    private function decodingFileContainer(): void
    {
        $this->sendRequest('post', "/ticket/{$this->ticketUuid}/decryptor", ['keyStorePassword' => '1111']);
    }

    /**
     * Get information about the keys to store the key container
     *
     * @param string $password Password for the session key container
     *
     * @return array
     * @throws ApiException
     */
    public function getFileContainerInfo(string $password): array
    {
        $response = $this->sendRequest('put', "/ticket/{$this->ticketUuid}/keyStore/verifier", ['keyStorePassword' => $password]);

        if (empty($response) || !isset($response['signature'])) {
            ErrorHandler::throwError(
                [
                    'message'      => __('api.cipher.failed_to_get_key_info'),
                    'failureCause' => 'Invalid or empty response from keyStore/verifier',
                    'api_response' => $response,
                ]
            );
        }

        return $response;
    }

    /**
     * Check if some important data received from the forms are have the same value as in the DS FileContainer
     *
     * @param string $taxId Tax ID (EDRPOU/DRFOU) for verification.
     * @param string $initiator Type of signatory (Business/Person).
     * @return void
     * @throws ApiException
     */
    public function verifyWithFileContainer(string $taxId, string $initiator): void
    {
        $cipherResponse = $this->getFileContainerInfo($this->password)['signature'];

        // If KEP key is not valid (ex. very old one)
        if (!($cipherResponse['canBeUsed'] ?? false)) {
            ErrorHandler::throwError(
                [
                    'message'      => __('validation.custom.cipher.kepNotValid'),
                    'failureCause' => 'Key cannot be used based on Cipher response.',
                ]
            );
        }

        $keyData = Arr::get($cipherResponse, 'certificateInfo.extensionsCertificateInfo.value.personalData.value');

        // Get value of 'edrpou' field for key's owner {string|null}
        $inKeyEdrpou = $keyData['edrpou']['value'] ?? null;

        // Check if the certificate belongs to the organization
        $isBusinessKey = !empty($inKeyEdrpou);

        // Get value of 'drfou' (IPN) field for key's owner {string|null}
        $inKeyDrfou = $keyData['drfou']['value'] ?? null;

        // Get last date when validity period is valid
        $endDate = $cipherResponse['certificateInfo']['notAfter']['value'] ?? null;

        if (empty($endDate)) {
            ErrorHandler::throwError(
                [
                    'message'      => __('validation.custom.cipher.kepEndDateMissing'),
                    'failureCause' => 'Certificate expiration date is missing.',
                ]
            );
        }

        $expirationDate = Carbon::parse($endDate);

        if ($expirationDate->lessThanOrEqualTo(Carbon::now())) {
            ErrorHandler::throwError(
                [
                    'message'      => __('validation.custom.cipher.kepTimeExpired'),
                    'failureCause' => 'Key has expired.',
                ]
            );
        }

        if ($initiator === self::SIGNATORY_INITIATOR_BUSINESS) {
            // Check if key is not a personal key
            if (!$isBusinessKey) {
                ErrorHandler::throwError(
                    [
                        'message'      => __('validation.custom.cipher.initiator_differ_business'),
                        'failureCause' => 'Business initiator expects a business key, but a personal key was provided.',
                    ]
                );
            }

            // Check for EDRPOU value match between key and form ones
            if (empty($inKeyEdrpou) || $inKeyEdrpou !== $taxId) {
                ErrorHandler::throwError([
                                             'message' => __('validation.custom.cipher.edrpouDiffer'),
                                             'failureCause' => 'EDRPOU in key does not match provided tax ID.'
                                         ]);
            }
        } else {
            // Check if key is a personal key
            if ($isBusinessKey) {
                ErrorHandler::throwError([
                                             'message' => __('validation.custom.cipher.initiator_differ_person'),
                                             'failureCause' => 'Person initiator expects a personal key, but a business key was provided.'
                                         ]);
            }
            // Check for DRFOU value match between key and form ones
            if (empty($inKeyDrfou) || $inKeyDrfou !== $taxId) {
                ErrorHandler::throwError([
                                             'message' => __('validation.custom.cipher.drfouDiffer'),
                                             'failureCause' => 'DRFOU in key does not match provided tax ID.'
                                         ]);
            }
        }
    }

    /**
     * @throws ApiException
     */
    public function getCertificateAuthorityApi(): array
    {
        $data = new Request('get', '/certificateAuthority/supported', '')->sendRequest();

        if ($data === false) {
            throw new \RuntimeException('Failed to fetch data from the API: sendRequest returned false.');
        }

        if (!isset($data['ca']) || !is_array($data['ca'])) {
            throw new \RuntimeException('Invalid response format: "ca" key is missing or not an array.');
        }

        return $data['ca'];
    }
}
