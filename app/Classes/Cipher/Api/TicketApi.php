<?php

declare(strict_types=1);

namespace App\Classes\Cipher\Api;

use App\Classes\Cipher\CipherClient;
use App\Classes\Cipher\CipherResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class TicketApi extends CipherClient
{
    /**
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function createSession(): CipherResponse|PromiseInterface
    {
        return $this->post('/ticket');
    }

    /**
     * @param string $ticketUuid
     * @param string $base64File
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function uploadKeyFile(string $ticketUuid, string $base64File): CipherResponse|PromiseInterface
    {
        return $this->put("/ticket/{$ticketUuid}/keyStore", ['base64Data' => $base64File]);
    }

    /**
     * @param string $ticketUuid
     * @param string $password
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function verifyKeyContainer(string $ticketUuid, string $password): CipherResponse|PromiseInterface
    {
        return $this->put("/ticket/{$ticketUuid}/keyStore/verifier", ['keyStorePassword' => $password]);
    }

    /**
     * @param string $ticketUuid
     * @param string $jsonToSign
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function uploadDataToSign(string $ticketUuid, string $jsonToSign): CipherResponse|PromiseInterface
    {
        return $this->post("/ticket/{$ticketUuid}/data", ['base64Data' => base64_encode($jsonToSign)]);
    }

    /**
     * @param string $ticketUuid
     * @param string $knedpId
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function setSessionParameters(string $ticketUuid, string $knedpId): CipherResponse|PromiseInterface
    {
        return $this->put("/ticket/{$ticketUuid}/option", [
            "caId" => $knedpId,
            "cadesType" => "CADES_X_LONG",
            "signatureType" => "attached",
            'embedDataTs' => 'true'
        ]);
    }

    /**
     * @param string $ticketUuid
     * @param string $password
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function initiateSignatureCreation(string $ticketUuid, string $password): CipherResponse|PromiseInterface
    {
        return $this->post("/ticket/{$ticketUuid}/ds/creator", ['keyStorePassword' => $password]);
    }

    /**
     * @param string $ticketUuid
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function getSignatureStatus(string $ticketUuid): CipherResponse|PromiseInterface
    {
        return $this->get("/ticket/{$ticketUuid}/ds/creator");
    }

    /**
     * @param string $ticketUuid
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function getSignedData(string $ticketUuid): CipherResponse|PromiseInterface
    {
        return $this->get("/ticket/{$ticketUuid}/ds/base64Data");
    }

    /**
     * @param string $ticketUuid
     *
     * @return CipherResponse|PromiseInterface
     * @throws ConnectionException
     */
    public function deleteSession(string $ticketUuid): CipherResponse|PromiseInterface
    {
        return $this->delete("/ticket/{$ticketUuid}");
    }
}
