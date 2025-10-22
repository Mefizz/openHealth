<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Events\EHealthUserLogin;
use App\Jobs\EmployeeRequestsSyncAll;
use App\Notifications\SyncNotification;
use Cache;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeUpdate
{
    /**
     * Handle the EHealthUserLogin event to trigger a daily synchronization of employee request statuses.
     *
     * This method uses a daily cache lock to ensure that the synchronization process
     * for a given legal entity is dispatched only once per day, regardless of how many
     * users log in. It creates and dispatches a batch containing the main sync job.
     *
     * @param  EHealthUserLogin  $event  The event containing user and legal entity context.
     * @return void
     * @throws Throwable
     */
    public function handle(EHealthUserLogin $event): void
    {
        $legalEntity = $event->legalEntity;
        $user = $event->user;

        // A unique cache key for the legal entity and the current date.
        $cacheKey = 'employee_request_sync_ran_for_' . $legalEntity->id . '_' . now()->toDateString();

        if (Cache::has($cacheKey)) {
            Log::info('[EmployeeUpdate] Daily sync for employee requests has already run. Skipping.');

            return;
        }

        // Set a cache lock that expires at the end of the day to prevent further dispatches.
        Cache::put($cacheKey, true, now()->endOfDay());

        Log::info('[EmployeeUpdate] Dispatching daily sync for employee requests.');

        Bus::batch([
                       new EmployeeRequestsSyncAll($legalEntity)
                   ])
            ->name('Full Employee Requests Sync for LE: ' . $legalEntity->id)
            ->withOption('legal_entity_id', $legalEntity->id)
            ->withOption('token', $event->token)
            ->withOption('user', $user)
            ->then(function () use ($user) {
                $user->notify(new SyncNotification('employee_request_full_sync', 'completed'));
            })
            ->catch(function (Batch $batch, Throwable $e) use ($user) {
                Log::error('Batch [Full Employee Requests Sync] failed.', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage()
                ]);
                $user->notify(new SyncNotification('employee_request_full_sync', 'failed'));
            })
            ->onQueue('sync')
            ->dispatch();

        $user->notify(new SyncNotification('employee_request_full_sync', 'started'));
    }
}
