<?php

namespace Izarych\FilamentOperations\Filament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Izarych\FilamentOperations\Filament\Pages\BackupBrowser;
use Izarych\FilamentOperations\Filament\Pages\LogViewer;

final class OperationsPlugin implements Plugin
{
    private static ?Closure $authorizeUsing = null;

    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'filament-operations';
    }

    public function authorizeUsing(Closure $callback): static
    {
        self::$authorizeUsing = $callback;

        return $this;
    }

    public function canAccess(): bool
    {
        return self::$authorizeUsing === null || (bool) app()->call(self::$authorizeUsing);
    }

    public function register(Panel $panel): void
    {
        $pages = [];

        if (config('filament-operations.backups.enabled')) {
            $pages[] = BackupBrowser::class;
        }

        if (config('filament-operations.logs.enabled')) {
            $pages[] = LogViewer::class;
        }

        $panel->pages($pages);
    }

    public function boot(Panel $panel): void {}
}
