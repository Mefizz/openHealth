<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function handle(EHealthUserLogin $event): void
    {
        $userParty = $event->user?->party;

        if (!$userParty || (!$userParty->uuid && !$userParty->tax_id)) {
            return;
        }

        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity);
        if ($pendingRequests->isEmpty()) {
            return;
        }

        try {
            $apiFilters = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'status'          => Status::APPROVED->value,
            ];

            if ($userParty->uuid) {
                $apiFilters['party_id'] = $userParty->uuid;
            } else {
                $apiFilters['tax_id'] = $userParty->tax_id;
            }

            $ehealthResponse = EHealth::employee()->getMany($apiFilters);
            $employeesFromApi = $ehealthResponse->getData();

        } catch (Throwable $e) {
            Log::error('Failed to fetch initial list for employee requests processing.', [
                'user_id'         => $event->user->id,
                'legal_entity_id' => $event->legalEntity->id,
                'error_message'   => $e->getMessage(),
            ]);
            return;
        }

        if (empty($employeesFromApi)) {
            return;
        }

        $approvedEmployeesByPosition = collect($employeesFromApi)->groupBy('position');

        foreach ($pendingRequests as $request) {
            try {
                if (!$approvedEmployeesByPosition->has($request->position)) {
                    continue;
                }

                $approvedData = $approvedEmployeesByPosition->get($request->position)
                    ?->firstWhere('employee_type', $request->employee_type);

                if (!$approvedData) {
                    continue;
                }

                $request->loadMissing('revision');
                if (!$request->revision || empty($request->revision->data)) {
                    Log::warning('Pending request is missing revision data.', ['request_id' => $request->id]);
                    continue;
                }

                $revisionData = $request->revision->data;
                $employeeData = $revisionData['employee_request_data'] ?? [];
                $partyData = $revisionData['party'] ?? [];
                $documents = $revisionData['documents'] ?? [];
                $phones = $revisionData['phones'] ?? [];
                $doctorData = $revisionData['doctor'] ?? [];


                $employeeData['uuid'] = $approvedData['id'];
                $employeeData['status'] = $approvedData['status'];
                $employeeData['legal_entity_uuid'] = $approvedData['legal_entity']['id'];
                $employeeData['is_active'] = $approvedData['is_active'] ?? true;

                DB::transaction(function () use ($employeeData, $partyData, $documents, $phones, $doctorData, $request) {
                    $employeeModel = Employee::updateOrCreate(
                        ['uuid' => $employeeData['uuid']],
                        $employeeData
                    );

                    Repository::employee()->updateDetails(
                        $employeeModel,
                        $partyData,
                        $documents,
                        $phones,
                        $doctorData['educations'] ?? null,
                        $doctorData['specialities'] ?? null,
                        $doctorData['qualifications'] ?? null,
                        $doctorData['scienceDegree'] ?? null
                    );

                    $request->status = Status::APPROVED->value;
                    $request->applied_at = now();
                    $request->employee()->associate($employeeModel);
                    $request->save();

                    if ($request->revision) {
                        $request->revision->update(
                            [
                                'status' => RevisionStatus::APPLIED->value,
                            ]
                        );
                    }
                });

            } catch (Throwable $e) {
                Log::error('Failed to process a single pending employee request.', [
                    'employee_request_id' => $request->id,
                    'error'               => $e->getMessage(),
                    'trace'               => $e->getTraceAsString(),
                ]);
                continue;
            }
        }
    }
}
