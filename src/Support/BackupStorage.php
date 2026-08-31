<?php

namespace Izarych\FilamentOperations\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use RuntimeException;

final class BackupStorage
{
    /**
     * @return list<array{path: string, name: string, size: int, size_formatted: string, last_modified: int, last_modified_formatted: string}>
     */
    public function backups(): array
    {
        $disk = $this->disk();

        return collect($disk->files($this->directory()))
            ->filter(fn (string $path): bool => $this->hasAllowedExtension($path))
            ->map(function (string $path) use ($disk): array {
                $lastModified = $disk->lastModified($path);
                $size = $disk->size($path);

                return [
                    'path' => $path,
                    'name' => basename($path),
                    'size' => $size,
                    'size_formatted' => Number::fileSize($size, precision: 2),
                    'last_modified' => $lastModified,
                    'last_modified_formatted' => Carbon::createFromTimestamp($lastModified)
                        ->setTimezone((string) config('app.timezone'))
                        ->format('d.m.Y H:i'),
                ];
            })
            ->sortByDesc('last_modified')
            ->values()
            ->all();
    }

    public function disk(): FilesystemAdapter
    {
        $diskName = (string) config('filament-operations.backups.disk');

        if ($diskName === '' || config("filesystems.disks.{$diskName}") === null) {
            throw new RuntimeException("Backup disk {$diskName} is not configured.");
        }

        return Storage::disk($diskName);
    }

    public function diskName(): string
    {
        return (string) config('filament-operations.backups.disk');
    }

    public function directory(): string
    {
        $directory = trim((string) config('filament-operations.backups.path'), '/');

        if ($directory === '') {
            throw new RuntimeException('Backup path is not configured.');
        }

        return $directory;
    }

    public function isDownloadable(string $path): bool
    {
        return preg_match('#\A'.preg_quote($this->directory(), '#').'/[^/]+\z#', $path) === 1
            && $this->hasAllowedExtension($path)
            && $this->disk()->exists($path);
    }

    public function contentType(string $path): string
    {
        return str_ends_with($path, '.zip') ? 'application/zip' : 'application/gzip';
    }

    private function hasAllowedExtension(string $path): bool
    {
        $extensions = config('filament-operations.backups.extensions', []);

        return is_array($extensions) && collect($extensions)
            ->filter(is_string(...))
            ->contains(fn (string $extension): bool => str_ends_with($path, '.'.ltrim($extension, '.')));
    }
}
