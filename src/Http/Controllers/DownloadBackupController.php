<?php

namespace Izarych\FilamentOperations\Http\Controllers;

use Illuminate\Http\Request;
use Izarych\FilamentOperations\Filament\OperationsPlugin;
use Izarych\FilamentOperations\Support\BackupStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadBackupController
{
    public function __invoke(Request $request, BackupStorage $backupStorage): StreamedResponse
    {
        abort_unless(OperationsPlugin::make()->canAccess(), 403);

        $path = $request->query('path');

        abort_unless(is_string($path) && $backupStorage->isDownloadable($path), 404);

        return $backupStorage->disk()->download($path, basename($path), [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => $backupStorage->contentType($path),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
