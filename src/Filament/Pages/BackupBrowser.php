<?php

namespace Izarych\FilamentOperations\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;
use Izarych\FilamentOperations\Filament\OperationsPlugin;
use Izarych\FilamentOperations\Support\BackupStorage;
use Throwable;
use UnitEnum;

final class BackupBrowser extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament-operations::pages.backup-browser';

    /** @var list<array<string, int|string>> */
    public array $backups = [];

    public int $totalSize = 0;

    public string $storageLabel = '';

    public ?string $loadError = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('filament-operations.navigation_group') ?? __('filament-operations::operations.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-operations::operations.backups.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-operations::operations.backups.title');
    }

    public static function canAccess(): bool
    {
        return OperationsPlugin::make()->canAccess();
    }

    public function mount(BackupStorage $backupStorage): void
    {
        $this->loadBackups($backupStorage);
    }

    public function refreshBackups(BackupStorage $backupStorage): void
    {
        $this->loadBackups($backupStorage);

        Notification::make()
            ->title($this->loadError === null ? __('filament-operations::operations.backups.notifications.updated') : __('filament-operations::operations.backups.notifications.update_failed'))
            ->color($this->loadError === null ? 'success' : 'danger')
            ->send();
    }

    /** @return list<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('filament-operations::operations.actions.refresh'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action('refreshBackups'),
        ];
    }

    private function loadBackups(BackupStorage $backupStorage): void
    {
        $this->storageLabel = $backupStorage->diskName();
        $this->loadError = null;

        try {
            $this->backups = collect($backupStorage->backups())
                ->map(fn (array $backup): array => [
                    ...$backup,
                    'download_url' => URL::temporarySignedRoute(
                        'filament-operations.backups.download',
                        now()->addMinutes(max(1, (int) config('filament-operations.backups.download_ttl_minutes', 60))),
                        ['path' => $backup['path']],
                    ),
                ])
                ->all();
            $this->totalSize = (int) collect($this->backups)->sum('size');
        } catch (Throwable $exception) {
            report($exception);
            $this->backups = [];
            $this->totalSize = 0;
            $this->loadError = $exception->getMessage();
        }
    }
}
