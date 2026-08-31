<?php

use Illuminate\Support\Facades\Route;
use Izarych\FilamentOperations\Http\Controllers\DownloadBackupController;

Route::middleware((array) config('filament-operations.download_route.middleware', ['web', 'auth', 'signed']))
    ->get(trim((string) config('filament-operations.download_route.prefix', 'filament-operations'), '/').'/backups/download', DownloadBackupController::class)
    ->name('filament-operations.backups.download');
