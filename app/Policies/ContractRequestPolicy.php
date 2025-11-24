<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractRequestPolicy
{
    /**
     * User allow to init contract request.
     */
    public function initialize(User $user): Response
    {
        if ($user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allow to create contract request.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allowed to synchronize contract request.
     */
    public function sync(User $user): Response
    {
        if ($user->cannot('contract_request:read') && $user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
