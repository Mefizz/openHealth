<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Events\EHealthUserLogin;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeUpdate implements ShouldQueue
{
    /**
     * Handle the user login event to synchronize employee data with E-Health.
     * This listener is designed to run once per day and requires specific permissions.
     */
    public function handle(EHealthUserLogin $event): void
    {
        try {
            $decryptedToken = Crypt::decryptString($event->token);
        } catch (Throwable $e) {
            Log::error('[EmployeeUpdate] Failed to decrypt token. Exiting.', ['error' => $e->getMessage()]);

            return;
        }

        $user = $event->user;
        $legalEntity = $event->legalEntity;

        // === STEP 1: Permission & Frequency Limiting Checks ===

        // The permission check ensures only authorized roles can trigger the sync.
        if (!$user->can('employee:read') || !$user->can('employee_request:read')) {
            // This is not an error but expected behavior for roles without access, so no log is needed.
            return;
        }

        // The "once per day" limit prevents redundant API requests on frequent logins.
        $cacheKey = "ehealth-update-ran-{$user->id}-{$legalEntity->id}";
        if (Cache::has($cacheKey)) {
            Log::info('[EmployeeUpdate] Exiting because a sync has already run today for this user/entity.');

            return;
        }

        Log::info('[EmployeeUpdate] Listener triggered. Permissions and cache checks passed.');

        try {
            // === STEP 2: Sync data for existing employees ===

            $localEmployees = Employee::where('legal_entity_id', $legalEntity->id)->whereNotNull('uuid')->get();
            if ($localEmployees->isNotEmpty()) {
                Log::info('[EmployeeUpdate] STEP 1: Found local employees to sync.', ['count' => $localEmployees->count()]);
                $employeeUuids = $localEmployees->pluck('uuid')->all();

                $eHealthEmployees = EHealth::employee()->getMany($decryptedToken, ['id' => $employeeUuids])->validate();
                Log::info('[EmployeeUpdate] STEP 1: Received employee data from E-Health.', ['count' => count($eHealthEmployees)]);

                DB::transaction(static function () use ($localEmployees, $eHealthEmployees) {
                    foreach ($eHealthEmployees as $eHealthEmployee) {
                        $localEmployee = $localEmployees->firstWhere('uuid', $eHealthEmployee['uuid']);
                        if ($localEmployee) {
                            $localEmployee->update(
                                [
                                    'status' => $eHealthEmployee['status'], 'is_active' => true,
                                    'position' => $eHealthEmployee['position'], 'employee_type' => $eHealthEmployee['employee_type'],
                                    'start_date' => $eHealthEmployee['start_date'], 'end_date' => $eHealthEmployee['end_date'],
                                ]
                            );
                        }
                    }
                });
                Log::info('[EmployeeUpdate] STEP 1: Employee records updated successfully.');
            }

            // === STEP 3: Sync statuses of local 'SIGNED' requests ===

            $localSignedRequests = EmployeeRequest::where('legal_entity_id', $legalEntity->id)
                ->where('status', RequestStatus::SIGNED)
                ->whereNotNull('uuid')
                ->get();

            Log::info('[EmployeeUpdate] STEP 2: Found local SIGNED requests to sync status.', ['count' => $localSignedRequests->count()]);

            if ($localSignedRequests->isNotEmpty()) {
                foreach ($localSignedRequests as $localRequest) {
                    try {
                        Log::info('[EmployeeUpdate] STEP 2: Checking status for single request.', ['uuid' => $localRequest->uuid]);

                        $eHealthResponse = EHealth::employeeRequest()->getMany($decryptedToken, ['id' => $localRequest->uuid])->validate();
                        $eHealthRequest = $eHealthResponse[0] ?? null;

                        if (!$eHealthRequest) {
                            continue;
                        }

                        if ($eHealthRequest['status'] !== 'NEW') {
                            $this->updateLocalRequestStatus($localRequest, $eHealthRequest['status']);
                        }

                    } catch (EHealthResponseException $e) {
                        if ($e->getCode() === 404) {
                            Log::info('[EmployeeUpdate] STEP 2: Request not found (404), likely processed.', ['uuid' => $localRequest->uuid]);
                        } else {
                            Log::error('[EmployeeUpdate] STEP 2: API error for single request.', ['uuid' => $localRequest->uuid, 'error' => $e->getMessage()]);
                        }
                    } catch (Throwable $e) {
                        Log::error('[EmployeeUpdate] STEP 2: General error while processing single request.', ['uuid' => $localRequest->uuid, 'error' => $e->getMessage()]);
                    }
                }
                Log::info('[EmployeeUpdate] STEP 2: Finished syncing request statuses.');
            }

            // === STEP 4: Cache the successful run ===
            // If all steps completed without critical errors, we cache the run to prevent re-execution until tomorrow.
            Cache::put($cacheKey, true, now()->addDay());
            Log::info('[EmployeeUpdate] Listener finished successfully. Caching run for 24 hours.');

        } catch (Throwable $e) {
            Log::error('!!! [EmployeeUpdate] CRITICAL LISTENER FAILURE !!!', [
                'user_id' => $user->id, 'error_message' => $e->getMessage(),
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            // Important: We DO NOT cache the run if an error occurs, allowing a retry on the next login.
        }
    }

    /**
     * Updates the status of a local EmployeeRequest and its associated revision.
     *
     * @param  EmployeeRequest  $localRequest  The local request model to update.
     * @param  string  $eHealthStatus  The status string received from E-Health.
     * @return void
     * @throws Throwable
     */
    private function updateLocalRequestStatus(EmployeeRequest $localRequest, string $eHealthStatus): void
    {
        $newLocalStatus = match($eHealthStatus) {
            'APPROVED' => RequestStatus::APPROVED,
            'REJECTED' => RequestStatus::REJECTED,
            'EXPIRED' => RequestStatus::EXPIRED,
            default => null
        };

        if ($newLocalStatus) {
            DB::transaction(static function () use ($localRequest, $newLocalStatus) {
                $localRequest->loadMissing('revision');
                $localRequest->status = $newLocalStatus;
                $localRequest->save();

                if ($localRequest->revision) {
                    $localRequest->revision->status = RevisionStatus::fromRequestStatus($newLocalStatus);
                    $localRequest->revision->save();
                }
            });
            Log::info('[EmployeeUpdate] Status updated for request.', ['uuid' => $localRequest->uuid, 'new_status' => $newLocalStatus->value]);
        }
    }
}
