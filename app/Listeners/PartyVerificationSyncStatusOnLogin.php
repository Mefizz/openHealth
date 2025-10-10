<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Events\EHealthUserLogin;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Handles the EHealthUserLogin event to synchronize party verification statuses.
 *
 * This listener is queued to prevent blocking the user's login process. It fetches
 * the latest verification statuses from the eHealth API for all parties associated
 * with the logged-in legal entity and updates the local database.
 */
class PartyVerificationSyncStatusOnLogin implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param EHealthUserLogin $event The event containing user and login context.
     * @return void
     */
    public function handle(EHealthUserLogin $event): void
    {
        Log::info('Listener ' . self::class . ' is executing sync for User ID: ' . $event->user->id);

        try {
            $token = Crypt::decryptString($event->token);

            // Fetch the list of verification statuses from the eHealth API.
            $verifications = EHealth::party()
                ->withToken($token)
                ->getMany()
                ->validate();

            if (empty($verifications)) {
                Log::info("eHealth API returned no verification data. Nothing to sync.");

                return;
            }

            // Prepare a map of [party_uuid => verification_status] for efficient updates.
            $updates = collect($verifications)->mapWithKeys(function ($verification) {
                return [data_get($verification, 'party_id') => data_get($verification, 'verification_status')];
            })->filter();

            if ($updates->isEmpty()) {
                Log::info("No valid party_id found in the API response. Nothing to update.");

                return;
            }

            $successfullyUpdatedCount = 0;

            foreach ($updates as $uuid => $newStatus) {
                // Find the local party record first to check its current status.
                $party = Party::where('uuid', $uuid)->first();

                if (!$party) {
                    Log::warning("Could not find a local Party with UUID: {$uuid} to update.");
                    continue;
                }

                $oldStatus = $party->verification_status;

                // Update the party record in the database.
                $party->update(['verification_status' => $newStatus]);
                $successfullyUpdatedCount++;

                // Check if the status has changed FROM 'VERIFIED' to something else.
                if ($oldStatus === 'VERIFIED' && $newStatus !== 'VERIFIED') {
                    // Find the user associated with this party.
                    $userToNotify = $party->user;

                    // If a user is found, send them a notification.
                    if ($userToNotify) {
                        $userToNotify->notify(new PartyVerificationStatusChanged($party, $newStatus));

                        Log::info("Sent 'VerificationStatusChanged' notification to User ID: {$userToNotify->id} for Party UUID: {$uuid}");
                    }
                }
            }

            Log::info(
                "Sync complete. Received {$updates->count()} parties from API, " .
                "successfully updated {$successfullyUpdatedCount} local records."
            );

        } catch (\Exception $e) {
            Log::error('Failed to sync party verification statuses on login.', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
