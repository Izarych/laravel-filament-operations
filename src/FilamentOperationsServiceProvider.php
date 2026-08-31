<?php

namespace Izarych\FilamentOperations;

use Illuminate\Support\ServiceProvider;
use Izarych\FilamentOperations\Filament\OperationsPlugin;

final class FilamentOperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-operations.php', 'filament-operations');

        $this->app->singleton(OperationsPlugin::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-operations');

        $this->publishes([
            __DIR__.'/../config/filament-operations.php' => config_path('filament-operations.php'),
        ], 'filament-operations-config');
    }
}
