# Laravel Filament Operations

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Filament](https://img.shields.io/badge/Filament-4%20%7C%205-FDAE4B?logo=filament&logoColor=white)](https://filamentphp.com/)
[![License](https://img.shields.io/badge/license-MIT-0ea5e9.svg)](LICENSE)

Operational pages for Filament: browse backups stored on any Laravel filesystem disk and inspect application logs without shell access.

The package intentionally does not create backups. Your application may use `spatie/laravel-backup`, a custom `mysqldump` command, or another process. This package lists the produced archives, generates short-lived download links, and provides a guarded log viewer.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Authorization](#authorization)
- [Backups](#backups)
- [Logs](#logs)
- [Updating](#updating)
- [Security](#security)
- [Русская версия](#русская-версия)

## Features

- Backup browser backed by any configured Laravel disk: local, S3-compatible storage, Yandex Object Storage, and others.
- Signed, expiring backup download links.
- Configurable archive directory and allowed extensions.
- Log browser for one or more local directories.
- Bounded tail preview: configurable line and byte limits prevent loading large logs into memory.
- Path traversal and symlink protections.
- Optional log clear and delete actions, disabled by default.
- A normal Filament plugin: register it only in panels where it belongs.

## Requirements

- PHP 8.3 or newer.
- Laravel 11 or 12.
- Filament 4.12+ or 5.x.
- An authenticated Filament panel.

## Installation

Install the package from Packagist and publish its configuration:

```bash
composer require izarych/laravel-filament-operations:^0.1
php artisan vendor:publish --tag=filament-operations-config
```

Register the plugin in the intended panel provider:

```php
use Filament\Panel;
use Izarych\FilamentOperations\Filament\OperationsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(OperationsPlugin::make());
}
```

The package is discovered by Laravel automatically. No provider registration is needed.

## Configuration

The published file is `config/filament-operations.php`. Values that vary between environments are read from `.env`.

```env
FILAMENT_OPERATIONS_NAVIGATION_GROUP="System"

FILAMENT_OPERATIONS_BACKUPS_ENABLED=true
FILAMENT_OPERATIONS_BACKUPS_DISK=yandexcloud
FILAMENT_OPERATIONS_BACKUPS_PATH="laravel-backup/Prod/my-app"
FILAMENT_OPERATIONS_BACKUPS_DOWNLOAD_TTL=60

FILAMENT_OPERATIONS_LOGS_ENABLED=true
FILAMENT_OPERATIONS_LOGS_LABEL="Application logs"
FILAMENT_OPERATIONS_LOGS_PATH="/var/www/my-app/storage/logs"
FILAMENT_OPERATIONS_LOGS_MAX_PREVIEW_BYTES=1048576
FILAMENT_OPERATIONS_LOGS_MAX_LINES=2000
FILAMENT_OPERATIONS_LOGS_ALLOW_CLEAR=false
FILAMENT_OPERATIONS_LOGS_ALLOW_DELETE=false
```

When configuration is cached, refresh it after changing environment values:

```bash
php artisan config:cache
```

## Authorization

The download route is protected by `web`, `auth`, and `signed` middleware by default. The Filament pages inherit access to the panel where the plugin is registered.

For a stricter rule, configure the plugin in the panel provider:

```php
->plugin(
    OperationsPlugin::make()
        ->authorizeUsing(fn (): bool => auth()->user()?->can('manage-operations') ?? false),
)
```

Use the same plugin instance only in panels that should expose these pages.

If your panel uses another guard, adjust `download_route.middleware` in `config/filament-operations.php`, for example:

```php
'middleware' => ['web', 'auth:admin', 'signed'],
```

## Backups

The backup browser reads files directly from the configured disk and directory. It accepts `zip` and `sql.gz` archives by default. Add or remove extensions in the package config:

```php
'extensions' => ['zip', 'sql.gz', 'dump'],
```

The package does not assume a naming convention. It only permits direct children of the configured directory; nested paths and arbitrary disk files cannot be downloaded through the package route.

### Spatie Laravel Backup

Point `FILAMENT_OPERATIONS_BACKUPS_DISK` and `FILAMENT_OPERATIONS_BACKUPS_PATH` to the same destination configured for `spatie/laravel-backup`. The package does not require Spatie as a dependency.

### Custom backup commands

For an application command that uploads `database.sql.gz` archives to S3 or local storage, configure the same disk and path. Nothing else is required.

## Logs

The default root is Laravel's `storage/logs`. Multiple roots can be configured in `config/filament-operations.php`:

```php
'roots' => [
    ['label' => 'Laravel', 'path' => storage_path('logs')],
    ['label' => 'Queue worker', 'path' => '/var/log/my-app'],
],
```

Only existing local directories are accepted. The viewer rejects absolute relative paths supplied by users, `..` segments, null bytes, and symlinks. Log mutation actions are off unless explicitly enabled.

## Updating

Releases follow semantic versioning while the package is stabilised under `0.x`.

```bash
composer update izarych/laravel-filament-operations
php artisan config:cache
```

For a specific release:

```bash
composer require izarych/laravel-filament-operations:^0.2
```

Review the release notes before every upgrade. Major versions may require configuration changes.

## Security

- Keep the backup disk private. The package streams files through a signed, authenticated route instead of exposing public object URLs.
- Limit access with `authorizeUsing()` in projects with multiple panel roles.
- Do not enable log deletion in production unless it is an intentional operational policy.
- Logs and backups may contain secrets. Treat access to these pages as privileged administrator access.

## License

MIT. See [LICENSE](LICENSE).

---

# Русская версия

`Laravel Filament Operations` добавляет в Filament две системные страницы: просмотр бэкапов и просмотр логов. Он не создаёт бэкапы сам, поэтому одинаково подходит для `spatie/laravel-backup`, собственной команды `mysqldump` или внешнего процесса резервного копирования.

## Возможности

- Просмотр архивов из любого Laravel disk: local, S3-совместимое хранилище, Yandex Object Storage и другие.
- Временные подписанные ссылки на скачивание.
- Настройка disk, пути, допустимых расширений и времени жизни ссылки через config и `.env`.
- Просмотр файлов из одного или нескольких локальных каталогов с логами.
- Безопасный просмотр конца больших файлов с ограничением числа строк и объёма.
- Защита от `../`, симлинков и обращения к файлам вне разрешённых каталогов.
- Очистка и удаление логов доступны только при явном включении.

## Установка

Установите пакет из Packagist и опубликуйте конфигурацию:

```bash
composer require izarych/laravel-filament-operations:^0.1
php artisan vendor:publish --tag=filament-operations-config
```

Подключите плагин в нужной Filament-панели:

```php
use Izarych\FilamentOperations\Filament\OperationsPlugin;

->plugin(OperationsPlugin::make())
```

Laravel обнаружит Service Provider автоматически.

## Пример `.env`

```env
FILAMENT_OPERATIONS_BACKUPS_ENABLED=true
FILAMENT_OPERATIONS_BACKUPS_DISK=yandexcloud
FILAMENT_OPERATIONS_BACKUPS_PATH="laravel-backup/Prod/my-app"
FILAMENT_OPERATIONS_BACKUPS_DOWNLOAD_TTL=60

FILAMENT_OPERATIONS_LOGS_ENABLED=true
FILAMENT_OPERATIONS_LOGS_PATH="/var/www/my-app/storage/logs"
FILAMENT_OPERATIONS_LOGS_MAX_PREVIEW_BYTES=1048576
FILAMENT_OPERATIONS_LOGS_MAX_LINES=2000
FILAMENT_OPERATIONS_LOGS_ALLOW_CLEAR=false
FILAMENT_OPERATIONS_LOGS_ALLOW_DELETE=false
```

После изменения `.env` на окружении с config cache выполните:

```bash
php artisan config:cache
```

## Доступ

По умолчанию страницы доступны авторизованным пользователям той панели, куда подключён плагин. Для проектов с несколькими ролями обязательно задайте дополнительную проверку:

```php
->plugin(
    OperationsPlugin::make()
        ->authorizeUsing(fn (): bool => auth()->user()?->can('manage-operations') ?? false),
)
```

Маршрут скачивания дополнительно защищён `web`, `auth` и `signed`. Для отдельного guard измените `download_route.middleware` в опубликованном конфиге.

## Бэкапы

Страница показывает файлы непосредственно из указанного disk и каталога. По умолчанию разрешены `.zip` и `.sql.gz`. Пакет принимает только файлы из корня заданного backup path: получить произвольный объект из storage через ссылку нельзя.

Для Spatie укажите те же disk и path, что заданы в `config/backup.php`. Для собственной команды достаточно загружать архивы в настроенное место.

## Логи

Стандартный каталог: `storage/logs`. При необходимости задайте несколько каталогов в `config/filament-operations.php`:

```php
'roots' => [
    ['label' => 'Laravel', 'path' => storage_path('logs')],
    ['label' => 'Queue worker', 'path' => '/var/log/my-app'],
],
```

Удаление и очистка выключены по умолчанию. Не включайте их в production без осознанной политики хранения логов.

## Обновления

Новые версии устанавливаются через Composer и Git tags:

```bash
composer update izarych/laravel-filament-operations
php artisan config:cache
```

Для перехода на конкретную новую ветку версий:

```bash
composer require izarych/laravel-filament-operations:^0.2
```

Перед обновлением проверяйте release notes: при смене major-версии могут изменяться настройки.
