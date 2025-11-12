<?php

namespace App\Models\Contracts;

use App\Models\Contract;

class CapitationContract extends Contract
{
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('type', static function ($builder) {
            $builder->where('type', 'capitation');
        });

        static::creating(static function ($model) {
            $model->type = 'capitation';
        });
    }
}
