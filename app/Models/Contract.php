<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\CapitationContract;
use App\Models\Contracts\ReimbursementContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type',
        'uuid',
        'start_date',
        'end_date',
        'status',
        'contractor_legal_entity_id',
        'contractor_owner_id',
        'contractor_payment_details',
        'contractor_rmsp_amount',
        'external_contractor_flag',
        'external_contractors',
        'nhs_signer_id',
        'nhs_signer_base',
        'nhs_payment_method',
        'is_active',
        'is_suspended',
        'issue_city',
        'nhs_contract_price',
        'contract_number',
        'contract_request_id',
        'contract_id',
        'status_reason',
        'inserted_by',
        'inserted_at',
        'updated_at',
        'id_form',
        'nhs_signed_date',
        'reason',
        'contractor_base',
        'signed_content_location',
        'skip_provision_deactivation',
        'statute_md5',
        'additional_document_md5',
        'legal_entity_id',
        'contractor_employee_divisions',
        'contractor_divisions',
        'nhs_legal_entity_id',
        'assignee_id',
        'data',
        'medical_programs',
        'contractor_signed',
        'misc',
        'updated_by',
    ];

    protected $casts = [
        'contractor_payment_details' => 'array',
        'external_contractors' => 'array',
        'contractor_employee_divisions' => 'array',
        'contractor_divisions' => 'array',
        'data' => 'array',
        'medical_programs' => 'array',
    ];

    protected $dates = [
                         'start_date',
                         'end_date',
                         'inserted_at',
                         'updated_at',
                         'nhs_signed_date',
    ];

    protected $table = 'contracts';
    protected string $discriminator = 'type';

    protected array $discriminatorMap = [
        'capitation' => CapitationContract::class,
        'reimbursement' => ReimbursementContract::class,
    ];

    public function newFromBuilder($attributes = [], $connection = null): Model
    {
        $attributes = (array) $attributes;

        if (isset($attributes[$this->discriminator])) {
            $type = $attributes[$this->discriminator];
            if (isset($this->discriminatorMap[$type])) {
                $class = $this->discriminatorMap[$type];
                if (class_exists($class)) {
                    return (new $class())->newInstance([], true)->setRawAttributes($attributes, true);
                }
            }
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('type', static function (Builder $builder) {
            $instance = new static();
            $builder->whereIn(
                $instance->discriminator,
                array_keys($instance->discriminatorMap)
            );
        });
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class, 'legal_entity_id', 'id');
    }
}
