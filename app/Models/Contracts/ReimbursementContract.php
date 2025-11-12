<?php

namespace App\Models\Contracts;

use App\Models\Contract;

class ReimbursementContract extends Contract
{
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('type', static function ($builder) {
            $builder->where('type', 'reimbursement');
        });

        static::creating(static function ($model) {
            $model->type = 'reimbursement';
        });
    }
}
