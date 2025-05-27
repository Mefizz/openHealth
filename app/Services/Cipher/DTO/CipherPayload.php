<?php

namespace App\Services\Cipher\DTO;

use Illuminate\Http\UploadedFile;
use App\Services\Cipher\Exceptions\CipherException;

class CipherPayload
{
    public function __construct(
        public readonly array $data,
        public readonly string $knedp,
        public readonly UploadedFile $keyFile,
        public readonly string $password
    ) {}

    public function convertKeyToBase64(): string
    {
        if (!$this->keyFile->isValid()) {
            throw new CipherException('Невалідний файл ключа.');
        }

        $content = file_get_contents($this->keyFile->getRealPath());
        if ($content === false) {
            throw new CipherException('Не вдалося прочитати файл ключа.');
        }

        return base64_encode($content);
    }
}

