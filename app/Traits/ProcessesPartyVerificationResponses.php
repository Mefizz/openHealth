<?php

declare(strict_types=1);

namespace App\Traits;

use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection; // Added for type hinting

trait ProcessesPartyVerificationResponses
{
    /**
     * Processes party verification statuses using an optimized upsert approach
     * while retaining conditional notifications.
     *
     * This method performs the following steps:
     * 1. Fetches the current state (status and user relation) of relevant parties from the local DB.
     * 2. Prepares data for a bulk update based on status changes identified from the eHealth response.
     * 3. Executes a single 'upsert' query to update all changed statuses efficiently.
     * 4. Iterates through the *original* local party data (fetched in step 1) to compare
     * old statuses with the new statuses from eHealth, sending notifications only
     * when a party's status specifically changes *from* 'VERIFIED'.
     *
     * @param  EHealthResponse  $response  The API response object containing verification statuses from eHealth.
     * @param  LegalEntity  $legalEntity  The legal entity context, used for notifications.
     * @return void
     */
    private function processPartyVerificationResponse(EHealthResponse $response, LegalEntity $legalEntity): void
    {
        $validatedData = $response->validate();
        // $eHealthStatuses is a collection keyed by party UUID: ['uuid' => 'STATUS_FROM_EHEALTH']
        $eHealthStatuses = $response->map($validatedData);

        if (empty($eHealthStatuses)) {
            Log::info("No party verification updates received from eHealth.");

            return;
        }

        /**
         * Step 1: Fetch the current state of relevant parties from the local database.
         * We load the 'user' relation eagerly to avoid N+1 queries later when sending notifications.
         * The result is keyed by UUID for quick lookups.
         *
         * @var Collection<string, Party> $localParties
         */
        $partyUuids = array_keys($eHealthStatuses);
        $localParties = Party::whereIn('uuid', $partyUuids)
            ->with('user') // Eager load user for notifications
            ->get()
            ->keyBy('uuid'); // Key by UUID for efficient access

        if ($localParties->isEmpty()) {
            Log::info("No local parties found matching the UUIDs from eHealth.", ['uuids_from_ehealth' => $partyUuids]);

            return;
        }
        Log::info("Found " . $localParties->count() . " local parties to check against eHealth statuses.");

        /**
         * Step 2: Prepare data for the bulk 'upsert' operation.
         * We only include parties whose status has actually changed to optimize the update.
         * We also explicitly set 'updated_at' as upsert doesn't handle timestamps automatically.
         */
        $upsertData = [];
        foreach ($eHealthStatuses as $uuid => $newStatus) {
            $party = $localParties->get($uuid);
            // Include only if the party exists locally and the status is different
            if ($party && $party->verification_status !== $newStatus) {
                $upsertData[] = [
                    'uuid' => $uuid,
                    'verification_status' => $newStatus,
                    'updated_at' => now(), // Manually set updated_at for upsert
                ];
            }
        }

        /**
         * Execute the single 'upsert' query to update all changed records in the database.
         */
        if (!empty($upsertData)) {
            Log::info("Performing upsert for " . count($upsertData) . " parties.");
            Party::upsert(
                $upsertData,
                ['uuid'], // The unique identifier column(s) to match records
                ['verification_status', 'updated_at'] // The columns to update if a match is found
            );
            $successfullyUpdatedCount = count($upsertData);
        } else {
            Log::info("No status changes detected. Skipping upsert.");
            $successfullyUpdatedCount = 0;
        }

        /**
         * Step 3: Determine and send notifications based on specific status changes.
         * We iterate through the original $localParties collection (which still holds the OLD statuses)
         * and compare them with the $newStatus obtained from eHealth.
         */
        foreach ($localParties as $uuid => $party) {
            // Get the new status from the eHealth data for comparison
            $newStatus = $eHealthStatuses[$uuid] ?? null;
            // Get the old status directly from the $party object fetched before the upsert
            $oldStatus = $party->verification_status;

            // Send notification ONLY if the status changed FROM 'VERIFIED' TO something else
            if ($newStatus && $oldStatus === 'VERIFIED' && $newStatus !== 'VERIFIED') {
                /** @var \App\Models\User|null $userToNotify Eager loaded user relationship */
                if ($userToNotify = $party->user) {
                    Log::info("Notifying user about status change.", ['user_id' => $userToNotify->id, 'party_uuid' => $uuid, 'old_status' => $oldStatus, 'new_status' => $newStatus]);
                    // Pass the $party object (with old data but correct relations), the new status, and legal entity
                    $userToNotify->notify(new PartyVerificationStatusChanged($party, $newStatus, $legalEntity));
                }
            }
        }

        $context = method_exists($this, 'job') ? '[Job]' : '[Listener]';
        Log::info("{$context} Upsert finished. {$successfullyUpdatedCount} party verification records were potentially updated.");
    }
}
