<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperEmployeeRole
 */
class EmployeeRole extends Model
{
    protected $fillable = [
        'uuid',
        'employee_id',
        'healthcare_service_id',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'ehealth_updated_at',
        'ehealth_updated_by'
    ];

    protected $hidden = [
        'id'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function healthcareService(): BelongsTo
    {
        return $this->belongsTo(HealthcareService::class);
    }
}
