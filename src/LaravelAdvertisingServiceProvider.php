<?php

namespace Ahinest\LaravelAdvertising;

use Ahinest\LaravelAdvertising\Console\ExpireAdvertisingCommand;
use Ahinest\LaravelAdvertising\Console\SeedAdvertisingCategoriesCommand;
use Illuminate\Support\ServiceProvider;

class LaravelAdvertisingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/advertising.php', 'advertising');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/advertising.php' => config_path('advertising.php')], 'advertising-config');
        $this->publishes([__DIR__.'/../stubs/models' => app_path('Models/Advertising')], 'advertising-models');
        // Las claves JSON se resuelven con el locale activo de la aplicación.
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([ExpireAdvertisingCommand::class, SeedAdvertisingCategoriesCommand::class]);

        if (config('advertising.routes.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }
}
