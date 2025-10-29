<?php

declare(strict_types=1);

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Model;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Qualification extends Model
{
    use HasCamelCasing;

    protected $hidden = [
        'id',
        'qualificationable_id',
        'qualificationable_type',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'type',
        'institution_name',
        'speciality',
        'issued_date',
        'certificate_number',
        'valid_to',
        'additional_info',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'valid_to' => 'date',
    ];

    public function qualificationable(): MorphTo
    {
        return $this->morphTo();
    }
}
