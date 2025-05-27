<?php

namespace App\DataTransferObjects;

class EmployeeRequestSubmissionData
{
    public function __construct(
        public readonly array $employeeData,
        public readonly string $knedp,
        public readonly string $signature,
        public readonly string $signedAt,
        public readonly array $metadata = []
    ) {}

    public function toLegacyFormat(): array
    {
        return [
            'employee' => $this->employeeData,
            'signature' => [
                'knedp' => $this->knedp,
                'signature' => $this->signature,
                'signed_at' => $this->signedAt
            ],
            'metadata' => $this->metadata
        ];
    }
}
