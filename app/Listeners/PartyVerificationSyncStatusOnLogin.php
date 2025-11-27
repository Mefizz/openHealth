<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Events\EHealthUserLogin;
use App\Jobs\PartyVerificationSync;
use App\Notifications\SyncNotification;
use App\Traits\ProcessesPartyVerificationResponses;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

class PartyVerificationSyncStatusOnLogin
{
    use ProcessesPartyVerificationResponses;

    /**
     * Handle the event using the hybrid sync pattern.
     */
    public function handle(EHealthUserLogin $event): void
    {
        try {
            $token = Crypt::decryptString($event->token);
        } catch (Throwable $e) {
            Log::error('Failed to decrypt token in listener ' . self::class, ['error' => $e->getMessage()]);

            return;
        }

        // Check if the token has the required scope
        if (!$this->tokenHasScope($token, PartyVerificationSync::SCOPE_REQUIRED)) {
            Log::info('Listener ' . self::class . ' skipped. Missing required scope: ' . PartyVerificationSync::SCOPE_REQUIRED, [
                'user_id' => $event->user->id
            ]);

            return;
        }

        Log::info('Listener ' . self::class . ' is executing for User ID: ' . $event->user->id);

        $user = $event->user;
        $legalEntity = $event->legalEntity;

        try {
            $response = EHealth::party()->withToken($token)->getMany();

            $this->processPartyVerificationResponse($response, $legalEntity);

            if ($response->isNotLast()) {
                Bus::batch([new PartyVerificationSync($legalEntity, null, false, 2)])
                    ->name('Party Verification Status Sync')
                    ->withOption('legal_entity_id', $legalEntity->id)
                    ->withOption('token', $event->token)
                    ->withOption('user', $user)
                    ->then(function (Batch $batch) use ($user) {
                        $user->notify(new SyncNotification('party_verification', 'completed'));
                        Log::info('Batch [Party Verification Status Sync] completed.', ['id' => $batch->id]);
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($user) {
                        $user->notify(new SyncNotification('party_verification', 'failed'));
                        Log::error('Batch [Party Verification Status Sync] failed.', ['id' => $batch->id, 'error' => $e->getMessage()]);
                    })
                    ->onQueue('sync')
                    ->dispatch();

                $user->notify(new SyncNotification('party_verification', 'started'));
            }

        } catch (Throwable $e) {
            Log::error('Failed to start party verification sync on login.', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Checks if the JWT token contains the specified scope.
     */
    private function tokenHasScope(string $token, string $requiredScope): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
        if (!isset($payload['scope'])) {
            return false;
        }

        // Scopes are usually space-separated strings in JWT
        $scopes = explode(' ', $payload['scope']);

        return in_array($requiredScope, $scopes, true);
    }
}
