<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Builder;

class ReimbursementContract extends Contract
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model) {
            $model->type = 'reimbursement';
        });

        static::addGlobalScope('reimbursement', static function (Builder $builder) {
            $builder->where('type', 'reimbursement');
        });
    }
}
