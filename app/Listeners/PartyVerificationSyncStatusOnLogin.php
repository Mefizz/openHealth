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
use Throwable;

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
        Log::info('Listener ' . self::class . ' is executing for User ID: ' . $event->user->id);

        try {
            $token = Crypt::decryptString($event->token);

            $response = EHealth::party()->getMany($token);
            $validatedData = $response->validate();
            $updates = $response->map($validatedData);

            if (empty($updates)) {
                Log::info("eHealth API returned no verification data. Nothing to sync.");
                return;
            }

            $successfullyUpdatedCount = 0;
            foreach ($updates as $uuid => $newStatus) {
                $party = Party::where('uuid', $uuid)->first();

                if (!$party) {
                    Log::warning("Could not find a local Party with UUID: {$uuid} to update.");
                    continue;
                }

                $oldStatus = $party->verification_status;

                if ($oldStatus === $newStatus) {
                    continue;
                }

                $party->update(['verification_status' => $newStatus]);
                $successfullyUpdatedCount++;

                if ($oldStatus === 'VERIFIED' && $newStatus !== 'VERIFIED') {
                    if ($userToNotify = $party->user) {
                        $userToNotify->notify(new PartyVerificationStatusChanged($party, $newStatus));
                        Log::info("Sent 'VerificationStatusChanged' notification to User ID: {$userToNotify->id}");
                    }
                }
            }

            Log::info("Sync complete. Updated {$successfullyUpdatedCount} local records.");

        } catch (Throwable $e) {
            Log::error('Failed to sync party verification statuses on login.', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
