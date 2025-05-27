<?php

namespace App\Services\Cipher;

use App\Classes\Cipher\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\Cipher\Exceptions\CipherException;

class CertificateAuthorityFetcher
{
    public function get(): array
    {
        return Cache::remember('knedp_certificate_authority', now()->addDays(7), function () {
            $data = (new Request('get', '/certificateAuthority/supported', ''))->sendRequest();

            if (!$data || !isset($data['ca'])) {
                throw new CipherException('Не вдалося отримати перелік ЦСК.');
            }

            return $data['ca'];
        });
    }
}
