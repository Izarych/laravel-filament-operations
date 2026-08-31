<?php

namespace Izarych\FilamentOperations;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
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
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'filament-operations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-operations');

        FilamentAsset::register([
            Css::make('filament-operations', __DIR__.'/../resources/css/filament-operations.css')->loadedOnRequest(),
        ], package: 'izarych/laravel-filament-operations');

        $this->publishes([
            __DIR__.'/../config/filament-operations.php' => config_path('filament-operations.php'),
        ], 'filament-operations-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/filament-operations'),
        ], 'filament-operations-translations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-operations'),
        ], 'filament-operations-views');
    }
}
