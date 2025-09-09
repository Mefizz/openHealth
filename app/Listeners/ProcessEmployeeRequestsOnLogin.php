<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    )
    {
    }

    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(EHealthUserLogin $event): void
    {
        try {
            $pendingRequests = $this->employeeRepository->findPendingRequestsForUser($event->user, $event->legalEntity);
            if ($pendingRequests->isEmpty()) {
                return;
            }

            $userParty = $event->user->party;
            if (!$userParty?->uuid) {
                Log::warning(
                    'User does not have a party UUID, cannot process employee requests.',
                    ['user_id' => $event->user->id]
                );
                return;
            }

            $ehealthResponse = EHealth::employee()->getMany(
                [
                    'legal_entity_id' => $event->legalEntity->uuid,
                    'party_id' => $userParty->uuid,
                    'status' => Status::APPROVED->value,
                ]
            );
            $employeesFromApi = $ehealthResponse->getData();

            if (empty($employeesFromApi)) {
                return;
            }

        } catch (Throwable $e) {
            Log::error('Failed to fetch initial data for employee requests processing.', [
                'user_id' => $event->user->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return;
        }

        $approvedEmployeesByPosition = collect($employeesFromApi)->groupBy('position');

        foreach ($pendingRequests as $request) {
            if (!$approvedEmployeesByPosition->has($request->position)) {
                continue;
            }

            $approvedData = $approvedEmployeesByPosition->get($request->position)
                ->firstWhere(
                    fn($employeeFromApi) => $employeeFromApi['start_date'] === $request->start_date->format('Y-m-d') &&
                        $employeeFromApi['employee_type'] === $request->employee_type
                );

            if (!$approvedData) {
                continue;
            }

            DB::transaction(function() use ($approvedData, $request) {
                $detailsResponse = EHealth::employee()->getDetails($approvedData['id']);
                $detailsData     = $detailsResponse->validate();

                $employeeModel = Employee::firstOrNew(['uuid' => $detailsData['uuid']]);

                $this->employeeRepository->processSyncedEmployee($employeeModel, $detailsData);

                $request->status = Status::APPROVED->value;
                $request->applied_at = Carbon::parse($approvedData['updated_at'] ?? 'now');
                $request->employee()->associate($employeeModel);

                $request->save();

                if ($request->revision) {
                    $request->revision->update(
                        [
                            'status' => RevisionStatus::APPLIED->value,
                            'deleted_at' => now(),
                        ]
                    );
                }
            });
        }
    }
}
