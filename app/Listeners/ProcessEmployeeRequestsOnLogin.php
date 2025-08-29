<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Employee\SyncEmployeeRequestsService;
use Illuminate\Support\Facades\Auth;
use Throwable;
use App\Events\EHealthUserLoggedIn;
use App\Repositories\EmployeeRepository; // Імпортуємо ваш репозиторій
use Illuminate\Support\Facades\Log;

/**
 * Цей слухач обробляє подію входу користувача через eHealth.
 * Він делегує всю логіку синхронізації EmployeeRequests відповідному репозиторію.
 * Слухач є СИНХРОННИМ.
 */
class ProcessEmployeeRequestsOnLogin
{
    public function __construct(private SyncEmployeeRequestsService $syncService)
    {
    }

    public function handle(EHealthUserLoggedIn $event): void
    {
        try {
            $this->syncService->execute(
                $event->user,
                $event->legalEntity,
                $event->authUserUUID
            );
        } catch (Throwable $e) {
            // Якщо ви все ще дебажите, можете залишити тут dd($e)
             dd($e);

            Log::error('Failed to sync employee requests on login.', [
                'user_id' => $event->user->id,
                'legal_entity_id' => $event->legalEntity->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
