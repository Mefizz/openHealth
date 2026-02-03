<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contracts\Contract;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    /**
     * Determine whether the user can view any contracts (list).
     */
    public function viewAny(User $user): Response
    {
        // Check if user is an active OWNER
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner) {
            return Response::allow();
        }

        if ($user->cannot('contract_request:read') && $user->cannot('contract:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view a specific signed contract.
     */
    public function view(User $user, Contract $contract): Response
    {
        // 1. Strict check: Contract must belong to the current Legal Entity
        if ((int)$contract->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        // 2. OWNER Check
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner) {
            return Response::allow();
        }

        // 3. Permission check
        if ($user->cannot('contract_request:read') && $user->cannot('contract:read')) {
            return Response::deny(__('contracts.policy.view_denied'));
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create contracts.
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
     * Determine whether the user can synchronize contracts with eHealth.
     */
    public function sync(User $user): Response
    {
        $isOwner = Employee::where('user_id', $user->id)
            ->activeOwners(legalEntity()->id)
            ->exists();

        if ($isOwner) {
            return Response::allow();
        }

        if ($user->cannot('contract_request:read')) {
            return Response::deny(__('contracts.policy.sync_denied'));
        }

        return Response::allow();
    }
}
