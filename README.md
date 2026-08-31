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
composer require izarych/laravel-filament-operations:^0.2
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

## Language

The interface follows Laravel's active locale. English and Russian are included. With `APP_LOCALE=ru`, the navigation group, pages, actions, confirmations, and empty states are displayed in Russian.

To adjust individual phrases, publish the translation files:

```bash
php artisan vendor:publish --tag=filament-operations-translations
```

Edit only the required lines in `lang/vendor/filament-operations/{locale}/operations.php`. Published lines override the package defaults and are preserved when the package is updated.

## Styling

The package loads its own responsive CSS only on its backup and log pages. It uses the active Filament color palette, so the page automatically follows the panel's primary color and dark mode.

After installing or updating the package, build Filament assets:

```bash
php artisan filament:assets
```

For small visual adjustments, register a CSS file after your panel's other assets:

```php
use Filament\Support\Assets\Css;

->assets([
    Css::make('operations-overrides', resource_path('css/filament/operations.css')),
])
```

The package's selectors are prefixed with `.filament-operations`, so custom rules stay isolated:

```css
.filament-operations__workspace {
    border-radius: 1.25rem;
}

.filament-operations__preview {
    background: #172033;
}
```

For a complete layout change, publish the Blade templates and edit the copies in your application:

```bash
php artisan vendor:publish --tag=filament-operations-views
```

Laravel will then use `resources/views/vendor/filament-operations/pages/` before the package templates. Re-run `php artisan filament:assets` after changing the package CSS or deploying a new release.

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
composer require izarych/laravel-filament-operations:^0.2
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

## Язык

Интерфейс использует активную локаль Laravel. В пакет уже включены русский и английский. При `APP_LOCALE=ru` раздел навигации, страницы, действия, подтверждения и пустые состояния будут на русском языке.

Чтобы изменить отдельные формулировки, опубликуйте переводы:

```bash
php artisan vendor:publish --tag=filament-operations-translations
```

Меняйте только нужные строки в `lang/vendor/filament-operations/{locale}/operations.php`. Опубликованные строки имеют приоритет над переводами пакета и не перезаписываются при обновлении.

## Стили

Пакет загружает собственный адаптивный CSS только на страницах бэкапов и логов. Он использует активную палитру Filament, поэтому автоматически подстраивается под основной цвет панели и тёмную тему.

После установки или обновления соберите assets Filament:

```bash
php artisan filament:assets
```

Для небольших изменений добавьте CSS-файл после остальных assets панели:

```php
use Filament\Support\Assets\Css;

->assets([
    Css::make('operations-overrides', resource_path('css/filament/operations.css')),
])
```

Все селекторы пакета начинаются с `.filament-operations`, поэтому стили не затронут остальные страницы:

```css
.filament-operations__workspace {
    border-radius: 1.25rem;
}

.filament-operations__preview {
    background: #172033;
}
```

Для полной переработки разметки опубликуйте Blade-шаблоны:

```bash
php artisan vendor:publish --tag=filament-operations-views
```

Laravel будет брать шаблоны из `resources/views/vendor/filament-operations/pages/` раньше package views. После изменения CSS пакета или деплоя новой версии снова выполните `php artisan filament:assets`.

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
