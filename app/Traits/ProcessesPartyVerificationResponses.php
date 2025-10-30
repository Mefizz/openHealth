<?php

declare(strict_types=1);

namespace App\Traits;

use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;
use Illuminate\Support\Facades\Log;

trait ProcessesPartyVerificationResponses
{
    /**
     * Processes party verification statuses using an optimized upsert approach.
     * We provide all NOT NULL columns (e.g., last_name, first_name) to the upsert data array.
     * This prevents 'NOT NULL violation' errors in the edge case where 'upsert'
     * attempts an INSERT (due to a race condition or other anomaly) instead of an UPDATE.
     *
     * @param  EHealthResponse  $response  The API response object.
     * @param  LegalEntity  $legalEntity  The legal entity context.
     * @return void
     * @throws \Throwable If the upsert operation fails.
     */
    private function processPartyVerificationResponse(EHealthResponse $response, LegalEntity $legalEntity): void
    {
        $validatedData = $response->validate();
        $eHealthStatuses = $response->map($validatedData);

        if (empty($eHealthStatuses)) {
            Log::info("No party verification updates received from eHealth.");

            return;
        }

        /**
         * Step 1: Fetch the current state of relevant parties from the local database.
         * We load the 'users' relation eagerly to avoid N+1 queries later when sending notifications.
         */
        $partyUuids = array_keys($eHealthStatuses);
        $localParties = Party::whereIn('uuid', $partyUuids)
            ->with('users')
            ->get()
            ->keyBy('uuid');

        if ($localParties->isEmpty()) {
            Log::info("No local parties found matching the UUIDs from eHealth.", ['uuids_from_ehealth' => $partyUuids]);

            return;
        }
        Log::info("Found " . $localParties->count() . " local parties to check against eHealth statuses.");

        /**
         * Step 2: Prepare data for the bulk 'upsert' operation.
         */
        $upsertData = [];
        foreach ($eHealthStatuses as $uuid => $newStatus) {
            $party = $localParties->get($uuid);
            if ($party && $party->verification_status !== $newStatus) {
                // Provide all NOT NULL fields to ensure INSERT succeeds
                // if `upsert` chooses that path instead of UPDATE.
                $upsertData[] = [
                    'uuid' => $uuid,
                    'verification_status' => $newStatus,
                    'last_name' => $party->last_name,   // Added to prevent NOT NULL violation
                    'first_name' => $party->first_name, // Added to prevent NOT NULL violation
                    // NOTE: Add any other non-nullable fields here if they exist
                ];
            }
        }

        /**
         * Step 3: Execute the single 'upsert' query.
         */
        $successfullyUpdatedCount = 0;
        if (!empty($upsertData)) {
            Log::info("Attempting upsert for " . count($upsertData) . " parties.");

            try {
                Party::upsert(
                    values: $upsertData,
                    uniqueBy: ['uuid'],
                    // Only the verification_status should be updated if the record exists.
                    update: ['verification_status']
                );

                $successfullyUpdatedCount = count($upsertData);
                Log::info("[UPSERT SUCCEEDED] Upsert finished (potentially updated {$successfullyUpdatedCount} records).");

            } catch (\Throwable $e) {
                Log::error('[UPSERT FAILED] The upsert call failed.', [
                    'error' => $e->getMessage(),
                    // Log the first item to avoid oversized logs
                    'first_item_passed_to_upsert' => $upsertData[0] ?? 'empty'
                ]);
                throw $e;
            }

        } else {
            Log::info("No status changes detected. Skipping upsert.");
        }

        /**
         * Step 4: Determine and send notifications based on specific status changes.
         */
        foreach ($localParties as $uuid => $party) {
            $newStatus = $eHealthStatuses[$uuid] ?? null;
            $oldStatus = $party->verification_status; // The status as it was before this job ran

            // Send notification ONLY if the status changed FROM 'VERIFIED' TO something else
            if ($newStatus && $oldStatus === 'VERIFIED' && $newStatus !== 'VERIFIED') {
                $usersToNotify = $party->users;
                foreach ($usersToNotify as $userToNotify) {
                    Log::info("Notifying user about status change.", ['user_id' => $userToNotify->id, 'party_uuid' => $uuid, 'old_status' => $oldStatus, 'new_status' => $newStatus]);
                    $userToNotify->notify(new PartyVerificationStatusChanged($party, $newStatus, $legalEntity));
                }
            }
        }

        $context = method_exists($this, 'job') ? '[Job]' : '[Listener]';
        Log::info("{$context} Verification status processing finished. {$successfullyUpdatedCount} records were targetted by the update operation.");
    }
}
