<?php

namespace App\Exceptions;

class DigitalSignatureException extends \Exception
{
    public function __construct(
        string $message,
        private ?string $signatureData = null
    ) {
        parent::__construct($message);
    }

    public function getSignatureData(): ?string
    {
        return $this->signatureData;
    }
}
