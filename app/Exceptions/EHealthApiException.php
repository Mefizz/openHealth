<?php

namespace App\Exceptions;

class EHealthApiException extends \Exception
{
    public function __construct(
        string $message,
        private ?int $statusCode = null,
        private ?array $responseData = null
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
