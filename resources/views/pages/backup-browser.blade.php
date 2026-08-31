<x-filament-panels::page>
    <div
        x-data="{}"
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-operations', package: 'izarych/laravel-filament-operations'))]"
        class="filament-operations filament-operations--backups"
    >
        @if ($loadError !== null)
            <x-filament::section :heading="__('filament-operations::operations.backups.load_failed')" :description="$loadError" icon="heroicon-o-exclamation-triangle" icon-color="danger" />
        @else
            <div class="filament-operations__stats">
                <article class="filament-operations__stat"><div class="filament-operations__stat-icon filament-operations__stat-icon--primary"><x-filament::icon icon="heroicon-o-archive-box" /></div><div><div class="filament-operations__stat-label">{{ __('filament-operations::operations.backups.count') }}</div><div class="filament-operations__stat-value">{{ count($backups) }}</div></div></article>
                <article class="filament-operations__stat"><div class="filament-operations__stat-icon filament-operations__stat-icon--success"><x-filament::icon icon="heroicon-o-circle-stack" /></div><div><div class="filament-operations__stat-label">{{ __('filament-operations::operations.backups.total_size') }}</div><div class="filament-operations__stat-value">{{ \Illuminate\Support\Number::fileSize($totalSize, precision: 2) }}</div></div></article>
                <article class="filament-operations__stat"><div class="filament-operations__stat-icon filament-operations__stat-icon--info"><x-filament::icon icon="heroicon-o-server-stack" /></div><div><div class="filament-operations__stat-label">{{ __('filament-operations::operations.backups.storage') }}</div><div class="filament-operations__stat-value filament-operations__stat-value--compact">{{ $storageLabel }}</div></div></article>
            </div>
            <section class="filament-operations__archive-panel">
                <header class="filament-operations__panel-header"><div><div class="filament-operations__panel-title">{{ __('filament-operations::operations.backups.archives') }}</div><div class="filament-operations__panel-description">{{ __('filament-operations::operations.backups.archives_description') }}</div></div></header>
                @if ($backups === [])
                    <x-filament::empty-state :heading="__('filament-operations::operations.backups.empty_heading')" :description="__('filament-operations::operations.backups.empty_description')" icon="heroicon-o-archive-box-arrow-down" />
                @else
                    <div class="filament-operations__archive-list">
                        @foreach ($backups as $backup)
                            <div class="filament-operations__archive" wire:key="backup-{{ sha1($backup['path']) }}"><span class="filament-operations__file-icon"><x-filament::icon icon="heroicon-o-archive-box" /></span><div class="filament-operations__file-main"><div class="filament-operations__file-name">{{ $backup['name'] }}</div><div class="filament-operations__file-meta">{{ $backup['size_formatted'] }} <span aria-hidden="true">•</span> {{ $backup['last_modified_formatted'] }}</div></div><x-filament::button tag="a" :href="$backup['download_url']" :spa-mode="false" icon="heroicon-m-arrow-down-tray" color="gray">{{ __('filament-operations::operations.backups.download') }}</x-filament::button></div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
