<?php

namespace App\Jobs;

use App\Models\Employee\Employee;
use App\Repositories\Repository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use App\Classes\eHealth\EHealth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeDetailsUpsert implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Employee $employee
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('ehealth-api')];
    }

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            $response = EHealth::employee()->getDetails($this->employee->uuid);
            $detailsData = $response->validate();

            Repository::employee()->processSyncedEmployee($this->employee, $detailsData);

        } catch (ValidationException $e) {

            Log::warning('Skipping employee sync due to invalid data from E-Health.', [
                'uuid' => $this->employee->uuid,
                'validation_errors' => $e->errors(),
            ]);

        } catch (Throwable $e) {

            Log::error('Job EmployeeDetailsUpsert failed for UUID: ' . $this->employee->uuid, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }
}
