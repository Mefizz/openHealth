<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\LegalEntity\LegalEntity;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        $this->app->singletonIf(LegalEntity::class, function () {

            return Auth::user()?->legalEntity;
        });

        $this->app->alias(LegalEntity::class, 'legalEntity');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Model::shouldBeStrict($this->app->isLocal());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
