<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EHealthUserLogin;
use Illuminate\Support\Facades\Log;

/**
 * Handles the immediate, synchronous setup of a user's session upon login.
 * This listener is responsible for preparing and locking the user's permissions
 * for the duration of the session.
 */
class SetupUserSessionOnLogin
{
    /**
     * Handle the event.
     *
     * @param EHealthUserLogin $event
     * @return void
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;
        $legalEntity = $event->legalEntity;
        $ehealthScopes = $event->ehealthScopes;

        // 1. Get user's actual roles from the database.
        $userRoles = $user->employees()
            ->where('legal_entity_id', $legalEntity->id)
            ->pluck('employee_type')
            ->unique()
            ->toArray();

        // 2. Determine if the scope fix is needed.
        $rolesThatNeedFix = ['OWNER', 'HR'];
        $needsFix = !empty(array_intersect($userRoles, $rolesThatNeedFix));

        // 3. Apply the fix ONLY if necessary.
        if ($needsFix && !in_array('party_verification:read', $ehealthScopes, true)) {
            $ehealthScopes[] = 'party_verification:read';
            Log::info('[FIX] Forcibly added "party_verification:read" scope for user ' . $user->id);
        }

        // 4. Sync permissions with the final list of scopes.
        $user->syncPermissions($ehealthScopes);

        // 5. "Freeze" the scopes in the session to ensure security.
        session(['session_scopes' => $ehealthScopes]);

        // 6. Refresh the user model to load the new permissions immediately.
        $user->refresh();
    }
}
