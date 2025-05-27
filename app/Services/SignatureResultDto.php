<?php

namespace App\Services;

class SignatureResultDto
{
    public function __construct(
        public array $response,
        public array $originalData
    ) {}
}
