<?php

namespace App\Providers;

use App\Classes\Cipher\CipherClient;
use App\Classes\Cipher\CipherRequest;
use Illuminate\Support\ServiceProvider;

class CipherServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(CipherRequest::class, CipherClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
