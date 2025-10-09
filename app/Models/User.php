<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Person\Person;
use App\Models\Relations\Party;
use App\Models\Employee\Employee;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Employee\EmployeeRequest;
use Spatie\Permission\Models\Permission;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles {
        getAllPermissions as getAllPermissionsTrait;
    }

    /**
     * Track if email verification was already sent
     */
    private static array $emailVerificationSent = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'email',
        'password',
        'secret_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['person'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the party associated with the user.
     *
     * @return HasOne
     */
    public function party(): HasOne
    {
        return $this->hasOne(Party::class);
    }

    // TODO: Check why need it for??????
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'legal_entity_id', 'legal_entity_id');
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * This need to override because trait HasProfilePhoto was disabled to remove 'name' attribute calling.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : $this->defaultProfilePhotoUrl();
    }

    /**
     * Get email verified at timestamp in camelCase
     *
     * @return null|string
     */
    public function getEmailVerifiedAtAttribute(): ?string
    {
        return $this->attributes['email_verified_at'];
    }

    /**
     * This need to override because trait HasProfilePhoto was disabled to remove 'name' attribute calling.
     *
     * @return string
     */
    public function defaultProfilePhotoUrl(): string
    {
        return '';
    }

    /**
     * Check if user has access to the Legal Entity with specified UUID.
     *
     * @param  string  $legalEntityUuid
     * @return bool
     */
    public function hasAccessToLegalEntityByUuid(string $legalEntityUuid): bool
    {
        return $this->employees()
            ->whereHas('legalEntity', fn (Builder $query) => $query->where('uuid', $legalEntityUuid))
            ->exists();
    }

    /**
     * Get ALL Legal Entities IDs available for this user
     *
     * @return Collection<int|string, mixed>
     */
    public function accessibleLegalEntities(): Collection
    {
        return $this->employees()
            ->with('legalEntity')
            ->get()
            ->unique('legal_entity_id')
            ->pluck('legal_entity_id');
    }

    /**
     * Overrides trait's method to exclude unused scopes
     * @return Collection<Permission> a list of scopes associated with the user and entity type
     */
    public function getAllPermissions(string $legalEntityClientId): Collection
    {
        $scopes = $this->getAllPermissionsTrait();

        $legalEntity = LegalEntity::where('client_id', $legalEntityClientId)->first();

        $exclude = []; // exclude scopes not used by the entity

        switch ($legalEntity->type) {
            case LegalEntity::TYPE_PRIMARY_CARE:
                $exclude = array_merge(
                    $exclude,
                    [
                        'contract_request:sign',
                        'contract_request:terminate',
                        'contract:write',
                        'contract_request:approve',
                        'contract_request:create'
                    ]
                );
                break;
        }

        return $scopes->filter(
            fn (Permission $permission) => !collect($exclude)->some(
                fn ($excluded) => Str::startsWith($permission->name, $excluded)
            )
        );
    }

    /**
     * Retrieves the scopes assigned to a specific user.
     *
     * @param  string  $legalEntityClientId
     * @return string The concatenated string of user's scopes
     */
    public function getScopes(string $legalEntityClientId): string
    {
        return $this->getAllPermissions($legalEntityClientId)->pluck('name')->unique()->join(' ');
    }

    /**
     * Get employee by priority with encounter:write permission.
     *
     * @return Employee|null
     */
    public function getEncounterWriterEmployee(): ?Employee
    {
        return $this->getWriterEmployeeByRolePriority(['DOCTOR', 'SPECIALIST', 'ASSISTANT', 'MED_COORDINATOR']);
    }

    /**
     * Get employee by priority with diagnostic_report:write permission.
     *
     * @return Employee|null
     */
    public function getDiagnosticReportWriterEmployee(): ?Employee
    {
        return $this->getWriterEmployeeByRolePriority(['DOCTOR', 'SPECIALIST', 'ASSISTANT', 'LABORANT']);
    }

    /**
     * Get employee by priority with procedure:write permission.
     *
     * @return Employee|null
     */
    public function getProcedureWriterEmployee(): ?Employee
    {
        return $this->getWriterEmployeeByRolePriority(['DOCTOR', 'SPECIALIST', 'ASSISTANT']);
    }

    /**
     * OVERRIDE: the parent method.
     * Send the email verification notification with error handling.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        // Check if we already sent verification to this email in this request
        $emailKey = $this->email . '_' . $this->id;

        // Already sent, skipping
        if (isset(self::$emailVerificationSent[$emailKey])) {
            return;
        }

        try {
            parent::sendEmailVerificationNotification();

            // Mark as sent
            self::$emailVerificationSent[$emailKey] = true;
        } catch (Exception $err) {
            Log::error('EmailVerification Error:', ['error' => $err->getMessage(), 'user_email' => $this->email]);

            throw new Exception(__("Cannot send verification email to the user"));
        }
    }

    /**
     * Override the default Spatie permission check to enforce session-based scopes.
     *
     * When a user logs in via eHealth, their scopes for that session are "locked".
     * This method ensures that even if a background job grants new roles/permissions
     * to the user in the database, their current session's capabilities do not exceed
     * what was originally granted by the eHealth API.
     *
     * @param string|Permission $permission
     * @param string|null $guardName
     * @return bool
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Check if session-locked scopes exist.
        if (session()?->has('session_scopes')) {
            $sessionScopes = session('session_scopes');

            // Get the permission name whether it's an object or a string.
            $permissionName = is_string($permission) ? $permission : $permission->name;

            // Check permission only against the session-locked list.
            return in_array($permissionName, $sessionScopes, true);
        }

        // If no session lock, fall back to the default Spatie behavior.
        return parent::hasPermissionTo($permission, $guardName);
    }

    /**
     * Get employee by priority with specific write permission. Example: procedure:write.
     *
     * @param  array  $priorityRoles  Ordered role from most valuable to least
     * @return Employee|null
     */
    protected function getWriterEmployeeByRolePriority(array $priorityRoles): ?Employee
    {
        $employees = $this->employees()
            ->select(['id', 'uuid', 'party_id', 'employee_type'])
            ->with('party:id,first_name,last_name,second_name')
            ->whereIn('employee_type', $priorityRoles)
            ->get();

        return $employees->sortBy(
            fn (Employee $employee) => array_search($employee->employee_type, $priorityRoles, true)
        )->first();
    }
}
