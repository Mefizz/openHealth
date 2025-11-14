<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Builder;

class CapitationContract extends Contract
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model) {
            $model->type = 'capitation';
        });

        static::addGlobalScope('capitation', static function (Builder $builder) {
            $builder->where('type', 'capitation');
        });
    }
}
