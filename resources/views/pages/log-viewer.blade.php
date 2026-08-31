<x-filament-panels::page>
    @if ($loadError !== null)
        <x-filament::section heading="Unable to load logs" :description="$loadError" icon="heroicon-o-exclamation-triangle" icon-color="danger" />
    @else
        <div class="grid gap-4 md:grid-cols-2">
            <x-filament::section compact heading="Files">{{ count($logs) }}</x-filament::section>
            <x-filament::section compact heading="Total size">{{ \Illuminate\Support\Number::fileSize($totalSize, precision: 2) }}</x-filament::section>
        </div>

        @if ($logs === [])
            <x-filament::empty-state heading="No log files found" description="Logs will appear here when the configured directories contain files." icon="heroicon-o-document-text" />
        @else
            <div class="grid gap-4 lg:grid-cols-3">
                <x-filament::section heading="Files" class="lg:col-span-1">
                    <div class="space-y-1">
                        @foreach ($logs as $log)
                            <button
                                type="button"
                                wire:key="log-{{ sha1($log['root'].'/'.$log['path']) }}"
                                wire:click="selectLog({{ \Illuminate\Support\Js::from($log['root']) }}, {{ \Illuminate\Support\Js::from($log['path']) }})"
                                @class([
                                    'w-full rounded-lg px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-white/5',
                                    'bg-primary-50 dark:bg-primary-500/10' => $selectedRoot === $log['root'] && $selectedLog === $log['path'],
                                ])
                            >
                                <p class="truncate font-medium">{{ $log['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $log['directory'] }} {{ $log['size_formatted'] }} · {{ $log['last_modified_formatted'] }}</p>
                            </button>
                        @endforeach
                    </div>
                </x-filament::section>

                <x-filament::section heading="{{ $selectedLog ?? 'Log preview' }}" class="lg:col-span-2">
                    <div class="mb-4 flex justify-end">
                        <select wire:change="setLineLimit($event.target.value)" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                            @foreach ([100, 250, 500, 1000, 2000] as $limit)
                                <option value="{{ $limit }}" @selected($lineLimit === $limit)>Last {{ $limit }} lines</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($contentError !== null)
                        <p class="text-danger-600">{{ $contentError }}</p>
                    @elseif ($selectedContent === '')
                        <p class="text-gray-500">The file is empty.</p>
                    @else
                        <pre class="max-h-[60vh] overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100"><code>{{ $selectedContent }}</code></pre>
                    @endif

                    <div class="mt-3 text-sm text-gray-500">
                        Displayed lines: {{ $displayedLines }}
                        @if ($contentTruncated)
                            <x-filament::badge color="warning" size="sm">Showing the end of the file</x-filament::badge>
                        @endif
                    </div>
                </x-filament::section>
            </div>
        @endif
    @endif
</x-filament-panels::page>
