<?php

namespace App\Services;

use App\Classes\Cipher\Api\CipherApi;
use App\Classes\Cipher\Exceptions\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SignatureService
{
    protected CipherApi $cipherApi;

    public function __construct(CipherApi $cipherApi)
    {
        $this->cipherApi = $cipherApi;
    }

    /**
     * Sends data for signing using Cipher API.
     *
     * @param array $dataToSign The data payload to be signed.
     * @param string $password Password for the key container.
     * @param string $knedp KNEPD (Certificate Authority ID).
     * @param string $base64FileContent The Base64 encoded content of the key container file.
     * @param string $signatoryInitiator Type of signatory (e.g., CipherApi::SIGNATORY_INITIATOR_PERSON).
     * @param string $taxId Tax ID (ІПН/ЄДРПОУ) for verification.
     * @return string|array The signed data as a string from Cipher API, or an array of errors.
     */
    public function signData(
        array $dataToSign,
        string $password,
        string $knedp,
        string $base64FileContent,
        string $signatoryInitiator,
        string $taxId,
    ): string|array {
        try {
            return $this->cipherApi->sendSession(
                json_encode($dataToSign, JSON_THROW_ON_ERROR),
                $password,
                $base64FileContent,
                $knedp,
                $signatoryInitiator,
                $taxId
            );
        } catch (ApiException $e) {
            Log::error('Cipher API Exception in SignatureService: ' . $e->getMessage(), ['errors' => $e->getErrors()]);
            return $e->getErrors();
        } catch (\Exception $e) {
            Log::error('Unexpected error in SignatureService: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'errors' => [
                    'general' => __('api.cipher.unexpected_error') . ': ' . $e->getMessage()
                ],
                'message' => __('api.cipher.unexpected_error_short'),
                'status' => 500
            ];
        }
    }

    /**
     * Retrieves supported certificate authorities from Cipher API, cached for 7 days.
     *
     * @return array An array of certificate authorities.
     */
    public function getCertificateAuthorities(): array
    {
        return Cache::remember('knedp_certificate_authority', now()->addDays(7), function () {
            try {
                return $this->cipherApi->getCertificateAuthorityApi();
            } catch (ApiException $e) {
                Log::error("Error fetching certificate authorities from Cipher API: " . $e->getMessage(), ['errors' => $e->getErrors()]);
                return [];
            } catch (\Exception $e) {
                Log::error("General error fetching certificate authorities: " . $e->getMessage(), ['exception' => $e]);
                return [];
            }
        });
    }
}
