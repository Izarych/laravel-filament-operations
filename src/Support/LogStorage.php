<?php

namespace Izarych\FilamentOperations\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileInfo;

final readonly class LogStorage
{
    public function __construct(private Filesystem $files) {}

    /**
     * @return list<array{root: string, path: string, name: string, directory: string, size: int, size_formatted: string, last_modified: int, last_modified_formatted: string}>
     */
    public function files(): array
    {
        return collect($this->roots())
            ->flatMap(function (array $root): array {
                return collect($this->files->allFiles($root['path']))
                    ->filter(fn (SplFileInfo $file): bool => $file->isFile() && ! $file->isLink())
                    ->map(function (SplFileInfo $file) use ($root): array {
                        $lastModified = $file->getMTime();
                        $size = $file->getSize();
                        $relativePath = Str::replace('\\', '/', $file->getRelativePathname());

                        return [
                            'root' => $root['key'],
                            'path' => $relativePath,
                            'name' => $file->getFilename(),
                            'directory' => trim(Str::replace('\\', '/', $file->getRelativePath()), '/'),
                            'size' => $size,
                            'size_formatted' => Number::fileSize($size, precision: 2),
                            'last_modified' => $lastModified,
                            'last_modified_formatted' => Carbon::createFromTimestamp($lastModified)
                                ->setTimezone((string) config('app.timezone'))
                                ->format('d.m.Y H:i:s'),
                        ];
                    })
                    ->all();
            })
            ->sortByDesc('last_modified')
            ->values()
            ->all();
    }

    /**
     * @return array{content: string, displayed_lines: int, truncated: bool}
     */
    public function tail(string $root, string $relativePath, int $lineLimit): array
    {
        $absolutePath = $this->resolve($root, $relativePath);
        $lineLimit = max(1, min($lineLimit, (int) config('filament-operations.logs.max_lines', 2000)));
        $maxBytes = max(1, (int) config('filament-operations.logs.max_preview_bytes', 1_048_576));
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open log {$relativePath}.");
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                throw new RuntimeException("Unable to lock log {$relativePath}.");
            }

            $position = (int) (fstat($handle)['size'] ?? 0);
            $buffer = '';

            while ($position > 0 && substr_count($buffer, "\n") <= $lineLimit && strlen($buffer) < $maxBytes) {
                $chunkSize = min(8192, $position, $maxBytes - strlen($buffer));
                $position -= $chunkSize;

                if (fseek($handle, $position) !== 0 || ($chunk = fread($handle, $chunkSize)) === false) {
                    throw new RuntimeException("Unable to read log {$relativePath}.");
                }

                $buffer = $chunk.$buffer;
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $truncated = $position > 0;

        if ($truncated && ($lineBreak = strpos($buffer, "\n")) !== false) {
            $buffer = substr($buffer, $lineBreak + 1);
        }

        $lines = $buffer === '' ? [] : preg_split('/\r\n|\r|\n/', rtrim(mb_scrub($buffer, 'UTF-8'), "\r\n"));

        if ($lines === false) {
            throw new RuntimeException("Unable to parse log {$relativePath}.");
        }

        if (count($lines) > $lineLimit) {
            $lines = array_slice($lines, -$lineLimit);
            $truncated = true;
        }

        return ['content' => implode("\n", $lines), 'displayed_lines' => count($lines), 'truncated' => $truncated];
    }

    public function clear(string $root, string $relativePath): void
    {
        $this->write($root, $relativePath, fn ($handle): bool => ftruncate($handle, 0) && fflush($handle));
    }

    public function delete(string $root, string $relativePath): void
    {
        if (! $this->files->delete($this->resolve($root, $relativePath))) {
            throw new RuntimeException("Unable to delete log {$relativePath}.");
        }
    }

    /** @return list<array{key: string, label: string, path: string}> */
    private function roots(): array
    {
        $roots = config('filament-operations.logs.roots', []);

        if (! is_array($roots)) {
            throw new RuntimeException('Log roots are not configured correctly.');
        }

        return collect($roots)->map(function (mixed $root, int $index): array {
            $path = is_array($root) ? (string) ($root['path'] ?? '') : '';

            if ($path === '' || ! $this->files->isDirectory($path)) {
                throw new RuntimeException("Log root {$path} is not available.");
            }

            return ['key' => (string) $index, 'label' => (string) ($root['label'] ?? $path), 'path' => $path];
        })->all();
    }

    private function resolve(string $rootKey, string $relativePath): string
    {
        $root = collect($this->roots())->firstWhere('key', $rootKey);
        $relativePath = Str::replace('\\', '/', trim($relativePath));

        if (! is_array($root) || $relativePath === '' || Str::startsWith($relativePath, '/') || Str::contains($relativePath, "\0") || in_array('..', explode('/', $relativePath), true)) {
            throw new RuntimeException('Invalid log file path.');
        }

        $directory = realpath($root['path']);
        $candidate = $root['path'].DIRECTORY_SEPARATOR.$relativePath;
        $resolvedPath = realpath($candidate);

        if ($directory === false || $resolvedPath === false || ! is_file($resolvedPath) || is_link($candidate) || ! Str::startsWith($resolvedPath, $directory.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Log file was not found.');
        }

        return $resolvedPath;
    }

    private function write(string $root, string $relativePath, callable $operation): void
    {
        $handle = fopen($this->resolve($root, $relativePath), 'c');

        if ($handle === false) {
            throw new RuntimeException("Unable to open log {$relativePath}.");
        }

        try {
            if (! flock($handle, LOCK_EX) || ! $operation($handle)) {
                throw new RuntimeException("Unable to update log {$relativePath}.");
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
