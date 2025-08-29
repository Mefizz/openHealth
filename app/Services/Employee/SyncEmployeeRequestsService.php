<?php

namespace App\Services\Employee;

use App\Classes\eHealth\Api\EmployeeApi;
use App\Models\LegalEntity;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service responsible for synchronizing local employee requests with E-Health.
 * It checks for pending requests and creates/updates employee records if they
 * have been approved in the E-Health system.
 */
class SyncEmployeeRequestsService
{
    /**
     * @param EmployeeRepository $employeeRepository The repository for database operations related to employees.
     */
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {
    }

    /**
     * Executes the synchronization process for a given user upon login.
     *
     * @param User $user The user who has just logged in.
     * @param LegalEntity $legalEntity The legal entity context of the login.
     * @param string $authUserUUID The user's UUID from the E-Health auth token.
     * @return void
     */
    public function execute(User $user, LegalEntity $legalEntity, string $authUserUUID): void
    {
        // Step 1: Use the repository to find all local "draft" requests for this user.
        $pendingRequests = $this->employeeRepository->findPendingRequestsForUser($user, $legalEntity);

        if ($pendingRequests->isEmpty()) {
            return;
        }

        $userParty = $user->party;
        if (!$userParty) {
            // Party is required to make the API call, so we can't proceed without it.
            return;
        }

        try {
            // Step 2: Make ONE optimized API call to fetch all approved employees for this person in this legal entity.
            $filterParams = [
                'legal_entity_id' => $legalEntity->uuid,
                'party_id'        => $userParty->uuid,
                'status'          => 'APPROVED',
            ];

            $approvedEmployeesList = EmployeeApi::getEmployeesList($filterParams);
            // If the API returns an empty list, there's nothing to sync.
            if (empty($approvedEmployeesList)) {
                return;
            }

            // Step 3: Create a map of approved employees with their position as the key for fast lookups.
            $approvedEmployeeMap = collect($approvedEmployeesList)->keyBy('position');


            // Step 4: Now, iterate through our local pending requests and check them against the map.
            foreach ($pendingRequests as $request) {
                if ($approvedEmployeeMap->has($request->position)) {
                    // Found a match! This local request corresponds to an approved employee in E-Health.
                    $approvedEmployeeData = $approvedEmployeeMap->get($request->position);

                    DB::transaction(function () use ($request, $user, $approvedEmployeeData, $authUserUUID, $legalEntity) {
                        $this->employeeRepository->createOrUpdateEmployeeFromEhealthData(
                            $user,
                            $request->party,
                            $approvedEmployeeData,
                            $authUserUUID,
                            $legalEntity->uuid
                        );

                        // Update the status of our local draft to 'APPROVED'.
                        $this->employeeRepository->updateEmployeeRequestStatus(
                            $request,
                            'APPROVED',
                            $approvedEmployeeData['updated_at'] ?? now()->toIso801String()
                        );

                        if ($request->revision) {
                            $request->revision->setApplied();
                        }
                    });
                }
            }
        } catch (Throwable $e) {
            Log::error('Failed during the employee synchronization process.', [
                'user_id' => $user->id,
                'legal_entity_id' => $legalEntity->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
