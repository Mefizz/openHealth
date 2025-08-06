<?php

declare(strict_types=1);

namespace App\Classes\Cipher;

use App\Classes\Cipher\Exceptions\CipherApiException;
use Illuminate\Http\Client\Response;

class CipherResponse extends Response
{
    /**
     * Common keys found in Cipher API responses.
     */
    public const string KEY_TICKET_UUID = 'ticketUuid';
    public const string KEY_BASE64_DATA = 'base64Data';
    public const string KEY_ERROR_MESSAGE = 'error.message';

    /**
     * @throws CipherApiException
     */
    public function throw(): self
    {
        if ($this->failed()) {
            // Try to get the most specific error message available.
            // The 'failureCause' is often the most user-friendly.
            $message = $this->json('failureCause');

            if (!$message) {
                // If 'failureCause' is not present, try the 'message' key.
                $message = $this->json('message');
            }

            if (!$message) {
                // As a last resort, check for the 'error.message' structure or use a default.
                $message = $this->json(self::KEY_ERROR_MESSAGE, 'An unknown Cipher API error occurred.');
            }

            throw new CipherApiException($message, $this, $this->status());
        }

        return $this;
    }

    /**
     * Get the ticket UUID from the response data.
     */
    public function getTicketUuid(): ?string
    {
        return $this->json(self::KEY_TICKET_UUID);
    }

    /**
     * Get the base64 encoded data from the response data.
     */
    public function getBase64Data(): ?string
    {
        return $this->json(self::KEY_BASE64_DATA);
    }
}
