<?php

namespace App\Traits;

use App\Models\LegalEntity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

trait HandlesLegalEntity
{
    public function resolveLegalEntity(): LegalEntity
    {
        return $this->getLegalEntityFromAuth() ?? $this->getLegalEntityFromCache() ?? new LegalEntity();
    }

    protected function getLegalEntityFromAuth(): ?LegalEntity
    {
        return Auth::user()->legalEntity ?? null;
    }

    protected function getLegalEntityFromCache(): ?LegalEntity
    {
        return Cache::get('legal_entity_for_user_' . Auth::id());
    }
}
