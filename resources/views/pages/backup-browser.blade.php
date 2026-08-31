<x-filament-panels::page>
    @if ($loadError !== null)
        <x-filament::section heading="Unable to load backups" :description="$loadError" icon="heroicon-o-exclamation-triangle" icon-color="danger" />
    @else
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section compact heading="Backups">{{ count($backups) }}</x-filament::section>
            <x-filament::section compact heading="Total size">{{ \Illuminate\Support\Number::fileSize($totalSize, precision: 2) }}</x-filament::section>
            <x-filament::section compact heading="Storage">{{ $storageLabel }}</x-filament::section>
        </div>

        <x-filament::section heading="Archives" description="The archive list is read directly from the configured private disk.">
            @if ($backups === [])
                <x-filament::empty-state heading="No backups found" description="Run your configured backup command to create an archive." icon="heroicon-o-archive-box-arrow-down" />
            @else
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($backups as $backup)
                        <div class="flex items-center gap-4 py-3" wire:key="backup-{{ sha1($backup['path']) }}">
                            <x-filament::icon icon="heroicon-o-archive-box" class="h-5 w-5 text-gray-500" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $backup['name'] }}</p>
                                <p class="text-sm text-gray-500">{{ $backup['last_modified_formatted'] }} · {{ $backup['size_formatted'] }}</p>
                            </div>
                            <x-filament::button tag="a" :href="$backup['download_url']" :spa-mode="false" icon="heroicon-m-arrow-down-tray" color="gray">Download</x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
