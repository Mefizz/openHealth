<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Status;
use App\Models\Contracts\ContractRequest;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractRequestPolicy
{
    /**
     * Determine whether the user can view any contract requests.
     */
    public function viewAny(User $user): Response
    {
        // 1.Owner verification
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner) {
            return Response::allow();
        }

        // 2. Checking Permissions
        if ($user->can('contract_request:read') || $user->can('contract_request:create')) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can view the contract request.
     */
  public function view(User $user, ContractRequest $contractRequest): Response
  {
      $uuidMatch = $contractRequest->contractor_legal_entity_id === legalEntity()->uuid;

      $employeeQuery = Employee::where('user_id', $user->id)
          ->where('legal_entity_id', legalEntity()->id)
          ->where('employee_type', 'OWNER')
          ->where('status', Status::APPROVED)
          ->where('is_active', true);

      $employee = $employeeQuery->first();

        // 1. Strict Ownership Check (UUID)
        if ($contractRequest->contractor_legal_entity_id !== legalEntity()->uuid) {
            return Response::denyWithStatus(404);
        }

        // 2. OWNER Check: Check if user has an active OWNER role in this Legal Entity
        // We use the scope you provided in the Employee model
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner) {
            return Response::allow();
        }

        // 3. Permission Check for regular employees
        if ($user->cannot('contract_request:read') && $user->cannot('contract_request:create')) {
            return Response::deny(__('contracts.policy.view_denied'));
        }

        return Response::allow();
    }

    /**
     * User allow to init contract request.
     */
    public function initialize(User $user): Response
    {
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner || $user->can('contract_request:create')) {
            return Response::allow();
        }

        return Response::denyWithStatus(404);
    }

    /**
     * User allow to create contract request.
     */
    public function create(User $user): Response
    {
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner || $user->can('contract_request:create')) {
            return Response::allow();
        }

        return Response::denyWithStatus(404);
    }

    /**
     * User allowed to synchronize contract request.
     */
    public function sync(User $user): Response
    {
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner) {
            return Response::allow();
        }

        if ($user->cannot('contract_request:read') && $user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
