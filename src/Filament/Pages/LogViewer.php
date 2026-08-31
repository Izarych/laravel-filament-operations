<?php

namespace Izarych\FilamentOperations\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Izarych\FilamentOperations\Filament\OperationsPlugin;
use Izarych\FilamentOperations\Support\LogStorage;
use Throwable;
use UnitEnum;

final class LogViewer extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Logs';

    protected static ?string $title = 'Application logs';

    protected static ?int $navigationSort = 110;

    protected string $view = 'filament-operations::pages.log-viewer';

    /** @var list<array<string, int|string>> */
    public array $logs = [];

    /** @var array<string, int|string>|null */
    public ?array $selectedLogMeta = null;

    public ?string $selectedRoot = null;

    public ?string $selectedLog = null;

    public string $selectedContent = '';

    public int $displayedLines = 0;

    public int $lineLimit = 250;

    public int $totalSize = 0;

    public bool $contentTruncated = false;

    public ?string $loadError = null;

    public ?string $contentError = null;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('filament-operations.navigation_group');
    }

    public static function canAccess(): bool
    {
        return OperationsPlugin::make()->canAccess();
    }

    public function mount(LogStorage $logStorage): void
    {
        $this->loadLogs($logStorage);
    }

    public function selectLog(string $root, string $path, LogStorage $logStorage): void
    {
        if (! collect($this->logs)->contains(fn (array $log): bool => $log['root'] === $root && $log['path'] === $path)) {
            return;
        }

        $this->selectedRoot = $root;
        $this->selectedLog = $path;
        $this->loadSelectedLog($logStorage);
    }

    public function setLineLimit(int $lineLimit, LogStorage $logStorage): void
    {
        if (! in_array($lineLimit, [100, 250, 500, 1000, 2000], true)) {
            return;
        }

        $this->lineLimit = $lineLimit;
        $this->loadSelectedLog($logStorage);
    }

    public function refreshLogs(LogStorage $logStorage): void
    {
        $this->loadLogs($logStorage);

        Notification::make()
            ->title($this->loadError === null ? 'Logs updated' : 'Unable to update logs')
            ->color($this->loadError === null ? 'success' : 'danger')
            ->send();
    }

    public function clearSelectedLog(LogStorage $logStorage): void
    {
        if (! config('filament-operations.logs.allow_clear') || $this->selectedRoot === null || $this->selectedLog === null) {
            return;
        }

        try {
            $logStorage->clear($this->selectedRoot, $this->selectedLog);
            $this->loadLogs($logStorage);
            Notification::make()->title('Log cleared')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Unable to clear log')->body($exception->getMessage())->danger()->send();
        }
    }

    public function deleteSelectedLog(LogStorage $logStorage): void
    {
        if (! config('filament-operations.logs.allow_delete') || $this->selectedRoot === null || $this->selectedLog === null) {
            return;
        }

        try {
            $logStorage->delete($this->selectedRoot, $this->selectedLog);
            $this->selectedRoot = null;
            $this->selectedLog = null;
            $this->loadLogs($logStorage);
            Notification::make()->title('Log deleted')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Unable to delete log')->body($exception->getMessage())->danger()->send();
        }
    }

    /** @return list<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action('refreshLogs'),
            Action::make('clear')
                ->label('Clear selected log')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('warning')
                ->visible(fn (): bool => (bool) config('filament-operations.logs.allow_clear') && $this->selectedLog !== null)
                ->requiresConfirmation()
                ->action('clearSelectedLog'),
            Action::make('delete')
                ->label('Delete selected log')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => (bool) config('filament-operations.logs.allow_delete') && $this->selectedLog !== null)
                ->requiresConfirmation()
                ->action('deleteSelectedLog'),
        ];
    }

    private function loadLogs(LogStorage $logStorage): void
    {
        $this->loadError = null;

        try {
            $this->logs = $logStorage->files();
            $this->totalSize = (int) collect($this->logs)->sum('size');
            $selectedExists = collect($this->logs)->contains(fn (array $log): bool => $log['root'] === $this->selectedRoot && $log['path'] === $this->selectedLog);

            if (! $selectedExists) {
                $this->selectedRoot = $this->logs[0]['root'] ?? null;
                $this->selectedLog = $this->logs[0]['path'] ?? null;
            }

            $this->loadSelectedLog($logStorage);
        } catch (Throwable $exception) {
            report($exception);
            $this->logs = [];
            $this->selectedRoot = null;
            $this->selectedLog = null;
            $this->selectedLogMeta = null;
            $this->selectedContent = '';
            $this->loadError = $exception->getMessage();
        }
    }

    private function loadSelectedLog(LogStorage $logStorage): void
    {
        $this->selectedLogMeta = collect($this->logs)
            ->first(fn (array $log): bool => $log['root'] === $this->selectedRoot && $log['path'] === $this->selectedLog);
        $this->selectedContent = '';
        $this->displayedLines = 0;
        $this->contentTruncated = false;
        $this->contentError = null;

        if ($this->selectedRoot === null || $this->selectedLog === null || $this->selectedLogMeta === null) {
            return;
        }

        try {
            $preview = $logStorage->tail($this->selectedRoot, $this->selectedLog, $this->lineLimit);
            $this->selectedContent = $preview['content'];
            $this->displayedLines = $preview['displayed_lines'];
            $this->contentTruncated = $preview['truncated'];
        } catch (Throwable $exception) {
            report($exception);
            $this->contentError = $exception->getMessage();
        }
    }
}
