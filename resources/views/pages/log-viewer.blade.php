<x-filament-panels::page>
    <div
        x-data="{}"
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-operations', package: 'izarych/laravel-filament-operations'))]"
        class="filament-operations log-viewer"
    >
        @if ($loadError !== null)
            <x-filament::section :heading="__('filament-operations::operations.logs.load_failed')" :description="$loadError" icon="heroicon-o-exclamation-triangle" icon-color="danger" />
        @else
            <div class="filament-operations__stats">
                <article class="filament-operations__stat">
                    <div class="filament-operations__stat-icon filament-operations__stat-icon--primary"><x-filament::icon icon="heroicon-o-document-duplicate" /></div>
                    <div><div class="filament-operations__stat-label">{{ __('filament-operations::operations.logs.files_count') }}</div><div class="filament-operations__stat-value">{{ count($logs) }}</div></div>
                </article>
                <article class="filament-operations__stat">
                    <div class="filament-operations__stat-icon filament-operations__stat-icon--success"><x-filament::icon icon="heroicon-o-circle-stack" /></div>
                    <div><div class="filament-operations__stat-label">{{ __('filament-operations::operations.logs.total_size') }}</div><div class="filament-operations__stat-value">{{ \Illuminate\Support\Number::fileSize($totalSize, precision: 2) }}</div></div>
                </article>
                <article class="filament-operations__stat">
                    <div class="filament-operations__stat-icon filament-operations__stat-icon--info"><x-filament::icon icon="heroicon-o-document-text" /></div>
                    <div><div class="filament-operations__stat-label">{{ __('filament-operations::operations.logs.selected_file') }}</div><div class="filament-operations__stat-value filament-operations__stat-value--compact">{{ $selectedLogMeta['size_formatted'] ?? '—' }}</div></div>
                </article>
            </div>

            @if ($logs === [])
                <x-filament::empty-state :heading="__('filament-operations::operations.logs.empty_heading')" :description="__('filament-operations::operations.logs.empty_description')" icon="heroicon-o-document-text" />
            @else
                <div class="filament-operations__workspace">
                    <aside class="filament-operations__files" :aria-label="__('filament-operations::operations.logs.files')">
                        <div class="filament-operations__panel-header"><div><div class="filament-operations__panel-title">{{ __('filament-operations::operations.logs.files') }}</div><div class="filament-operations__panel-description">{{ __('filament-operations::operations.logs.files_description') }}</div></div></div>
                        <div class="filament-operations__file-list">
                            @foreach ($logs as $log)
                                <button type="button" wire:key="log-{{ sha1($log['root'].'/'.$log['path']) }}" wire:click="selectLog({{ \Illuminate\Support\Js::from($log['root']) }}, {{ \Illuminate\Support\Js::from($log['path']) }})" @class(['filament-operations__file', 'filament-operations__file--active' => $selectedRoot === $log['root'] && $selectedLog === $log['path']])>
                                    <span class="filament-operations__file-icon"><x-filament::icon icon="heroicon-o-document-text" /></span>
                                    <span class="filament-operations__file-main">
                                        <span class="filament-operations__file-name" title="{{ $log['path'] }}">{{ $log['name'] }}</span>
                                        @if ($log['directory'] !== '')<span class="filament-operations__file-path">{{ $log['directory'] }}</span>@endif
                                        <span class="filament-operations__file-meta">{{ $log['size_formatted'] }} <span aria-hidden="true">•</span> {{ $log['last_modified_formatted'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </aside>

                    <section class="filament-operations__preview">
                        <div class="filament-operations__panel-header filament-operations__preview-header">
                            <div class="filament-operations__preview-title-wrap"><div class="filament-operations__panel-title" title="{{ $selectedLog }}">{{ $selectedLog ?? __('filament-operations::operations.logs.not_selected') }}</div>@if ($selectedLogMeta !== null)<div class="filament-operations__panel-description">{{ $selectedLogMeta['size_formatted'] }} · {{ __('filament-operations::operations.logs.modified', ['date' => $selectedLogMeta['last_modified_formatted']]) }}</div>@endif</div>
                            <div class="filament-operations__controls">
                                <label class="filament-operations__line-limit"><span>{{ __('filament-operations::operations.logs.last_lines') }}</span><select wire:change="setLineLimit($event.target.value)">@foreach ([100, 250, 500, 1000, 2000] as $limit)<option value="{{ $limit }}" @selected($lineLimit === $limit)>{{ __('filament-operations::operations.logs.lines', ['count' => $limit]) }}</option>@endforeach</select></label>
                                <x-filament::icon-button wire:click="refreshSelectedLog" icon="heroicon-o-arrow-path" color="gray" :label="__('filament-operations::operations.logs.refresh_content')" />
                            </div>
                        </div>

                        @if ($contentError !== null)
                            <div class="filament-operations__preview-message filament-operations__preview-message--error"><x-filament::icon icon="heroicon-o-exclamation-triangle" /><span>{{ $contentError }}</span></div>
                        @elseif ($selectedContent === '')
                            <div class="filament-operations__preview-message"><x-filament::icon icon="heroicon-o-document" /><span>{{ __('filament-operations::operations.logs.empty_file') }}</span></div>
                        @else
                            <pre class="filament-operations__content"><code>{{ $selectedContent }}</code></pre>
                        @endif

                        <footer class="filament-operations__preview-footer"><span>{{ __('filament-operations::operations.logs.displayed_lines', ['count' => $displayedLines]) }}</span>@if ($contentTruncated)<x-filament::badge color="warning" size="sm">{{ __('filament-operations::operations.logs.truncated') }}</x-filament::badge>@else<x-filament::badge color="success" size="sm">{{ __('filament-operations::operations.logs.complete') }}</x-filament::badge>@endif</footer>
                    </section>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
