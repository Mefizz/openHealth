<?php

declare(strict_types=1);

namespace App\Models\Person;

use App\Models\Employee\Employee;
use App\Models\MedicalEvents\Sql\Encounter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperPerson
 */
class Person extends BasePerson
{
    public function __construct()
    {
        parent::__construct();
        $this->mergeFillable(['death_date']);
    }

    protected static function booted(): void
    {
        static::creating(function ($party) {
            if (empty($party->uuid)) {
                $party->uuid = (string) Str::uuid();
            }
        });
    }

    protected $table = 'persons';

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function personRequest(): HasOne
    {
        return $this->hasOne(PersonRequest::class);
    }
}
