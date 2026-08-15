# Filament PWA Plugin

[![Latest Version on Packagist](https://img.shields.io/packagist/v/edu17/filament-pwa-plugin.svg?style=flat-square)](https://packagist.org/packages/edu17/filament-pwa-plugin)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/edu17/filament-pwa-plugin/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/edu17/filament-pwa-plugin/actions/workflows/tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/edu17/filament-pwa-plugin/fix-code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/edu17/filament-pwa-plugin/actions/workflows/fix-code-style.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/edu17/filament-pwa-plugin.svg?style=flat-square)](https://packagist.org/packages/edu17/filament-pwa-plugin)

Turn any FilamentPHP 4 panel into an installable Progressive Web App. Each panel receives its own manifest, service-worker scope, offline fallback, icons, and optional database-backed settings page.

## Features

- Independent PWA configuration for every Filament panel.
- Installable web app manifest and panel-scoped service worker.
- Safe offline fallback without caching authenticated HTML or Livewire requests.
- Fluent, configuration-file, and database-backed settings.
- Optional settings page hosted on the same panel or a separate administration panel.
- Access through panel authentication, a Laravel Gate, or a custom callback.
- Automatic 192×192, 512×512, maskable, and Apple touch icon generation from one image.

## Requirements

- PHP 8.2 or later.
- FilamentPHP 4.
- HTTPS in production. Browsers allow service workers on `localhost` during local development.
- GD or Imagick when using automatic icon generation.
- A publicly accessible filesystem disk when uploading icons from the settings page.

## Installation

Install the package:

```bash
composer require edu17/filament-pwa-plugin
```

Run the package installer:

```bash
php artisan filament-pwa-plugin:install
```

The installer publishes the configuration, migration, default icons, and Filament assets. It asks whether it should run the migrations. Answer yes, or run them later with:

```bash
php artisan migrate
```

Register the plugin on every panel that should become a PWA:

```php
use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentPwaPlugin::make());
}
```

The PWA endpoints are created below the panel path. A panel at `/admin` receives:

- `/admin/manifest.webmanifest`
- `/admin/pwa-service-worker.js`
- `/admin/offline`

### Manual installation

Instead of the installer, each step can be run manually:

```bash
php artisan vendor:publish --tag="filament-pwa-plugin-config"
php artisan vendor:publish --tag="filament-pwa-plugin-migrations"
php artisan vendor:publish --tag="filament-pwa-plugin-assets"
php artisan migrate
php artisan filament:assets
```

When the settings page uses the default `public` disk, ensure Laravel's storage link exists:

```bash
php artisan storage:link
```

### Installing the development branch

Before a stable release is available on Packagist, the package can be installed directly from GitHub:

```bash
composer config repositories.filament-pwa-plugin vcs https://github.com/edu17/filament-pwa-plugin
composer require edu17/filament-pwa-plugin:dev-main
```

## Basic configuration

The published `config/pwa-plugin.php` file defines the defaults shared by all registered panels:

```php
return [
    'enabled' => true,

    'manifest' => [
        'name' => config('app.name', 'Filament'),
        'short_name' => config('app.name', 'Filament'),
        'description' => null,
        'id' => null,
        'start_url' => null,
        'scope' => null,
        'display' => 'standalone',
        'orientation' => 'any',
        'theme_color' => '#18181b',
        'background_color' => '#ffffff',
    ],

    'offline' => [
        'cache_version' => '1',
        'precache' => [],
        'title' => 'You are offline',
        'message' => 'Check your internet connection and try again.',
        'retry_label' => 'Try again',
    ],

    'settings' => [
        'enabled' => false,
        'navigation_panels' => [],
        'manageable_panels' => [],
        'ability' => null,
        'disk' => 'public',
        'directory' => 'filament-pwa-plugin',
    ],
];
```

Leave `manifest.id`, `manifest.start_url`, and `manifest.scope` as `null` to derive them from each panel path. This is the recommended setting for applications with multiple panels.

After changing a cached configuration file, clear Laravel's configuration cache:

```bash
php artisan optimize:clear
```

## Per-panel fluent configuration

Manifest values can be overridden on an individual panel:

```php
FilamentPwaPlugin::make()
    ->name('Administration')
    ->shortName('Admin')
    ->description('Manage the application.')
    ->themeColor('#f59e0b')
    ->backgroundColor('#ffffff')
    ->display('standalone')
    ->orientation('any')
    ->startUrl('/admin')
    ->scope('/admin')
    ->manifestId('/admin');
```

Every fluent option accepts a closure that receives the current `Panel`:

```php
FilamentPwaPlugin::make()
    ->name(fn (Panel $panel): string => "{$panel->getId()} app")
    ->enabled(fn (Panel $panel): bool => $panel->getId() !== 'internal');
```

Values saved from the settings page take precedence over fluent values and configuration defaults. Fluent values take precedence over `config/pwa-plugin.php`. The database overrides apply only while `settings.enabled` is `true`.

## Settings page

The settings page can edit the application name, short name, description, colors, display mode, orientation, offline text, cache version, and icons independently for every managed panel.

The migration must be executed before setting `settings.enabled` to `true`.

### Separate administration and PWA panels

For example, to display the settings page in `superadmin` while managing the PWA installed at `admin`:

```php
// config/pwa-plugin.php
'settings' => [
    'enabled' => true,
    'navigation_panels' => ['superadmin'],
    'manageable_panels' => ['admin'],
    'ability' => null,
    'disk' => 'public',
    'directory' => 'filament-pwa-plugin',
],
```

Register the plugin on both panels. It can remain disabled as a PWA on the panel that only hosts the settings page:

```php
// SuperadminPanelProvider.php
->plugin(
    FilamentPwaPlugin::make()
        ->enabled(false),
)

// AdminPanelProvider.php
->plugin(
    FilamentPwaPlugin::make()
        ->name('My application'),
)
```

### One panel

A single panel can host the settings page and be the PWA it manages:

```php
// config/pwa-plugin.php
'settings' => [
    'enabled' => true,
    'navigation_panels' => ['admin'],
    'manageable_panels' => ['admin'],
    'ability' => null,
    'disk' => 'public',
    'directory' => 'filament-pwa-plugin',
],
```

Register `FilamentPwaPlugin::make()` once in the `admin` panel provider.

### Multiple panels

Both configuration values accept multiple panel IDs:

```php
'navigation_panels' => ['superadmin'],
'manageable_panels' => ['admin', 'staff', 'customer'],
```

Every panel in either list must register the plugin. A managed panel can use `->enabled(false)` when it should be configurable but should not expose PWA endpoints.

## Settings authorization

The authorization method is optional and does not depend on a roles package.

### Panel authentication only

Keep `settings.ability` as `null`. Any authenticated user who can access the panel containing the settings page can manage the configured PWA panels:

```php
'ability' => null,
```

This is appropriate when access is already separated through Filament panels.

### Laravel Gate or permission

Set an ability name:

```php
'ability' => 'manage-pwa-settings',
```

Define the corresponding Gate in the application:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define(
    'manage-pwa-settings',
    fn (User $user): bool => $user->is_super_admin,
);
```

Packages that integrate permissions with Laravel's `can()` method can use a permission name as the ability.

### Custom callback

Authorization can also be defined directly on the plugin instance that hosts the settings page:

```php
FilamentPwaPlugin::make()
    ->settingsPage()
    ->managePanels(['admin'])
    ->authorizeSettingsUsing(
        fn ($user, Panel $panel): bool => $user?->is_super_admin === true,
    );
```

The callback receives the authenticated user and the panel that hosts the settings page.

## Icons

The installation command publishes default icons to `public/vendor/filament-pwa-plugin/icons`. They can be replaced manually or configured through the `icons` and `apple_touch_icon` options.

The manifest uses:

- A 192×192 PNG icon.
- A 512×512 PNG icon.
- A maskable 512×512 PNG icon.

The Apple 180×180 icon is rendered separately as an `apple-touch-icon` link in the panel HTML.

The settings page accepts one square image of at least 512×512 pixels and up to 5 MB. It generates all four PNG variants. Generated files are stored on `settings.disk` under `settings.directory/{panel}/icons`.

## Offline behavior

The service worker is scoped to the exact panel path. A panel at `/admin` handles `/admin` and `/admin/...`, but it does not handle `/administrator` or the rest of the Laravel application.

The default strategy:

- Precaches the offline fallback and configured PWA icons.
- Uses the network for page navigations.
- Shows the offline fallback when a navigation request fails.
- Runtime-caches same-origin scripts, styles, and fonts.
- Never intercepts non-GET or cross-origin requests.
- Does not runtime-cache authenticated HTML, Livewire responses, API responses, or ordinary uploaded images.

Increment `offline.cache_version` after changing resources that may already be cached. Additional public same-origin URLs can be added to `offline.precache`.

## Verify the installation

Replace `admin` with the path of the target panel and verify:

1. Open `/admin/manifest.webmanifest` and confirm that it returns JSON with the expected `name`, `start_url`, `scope`, and icons.
2. Open `/admin/pwa-service-worker.js` and confirm that it returns JavaScript with a `Service-Worker-Allowed: /admin` header.
3. Open `/admin/offline` and confirm that the offline page renders.
4. Open the panel over HTTPS and confirm in the browser developer tools that the service worker is activated and the manifest is installable.
5. Test the offline fallback by enabling the browser's offline network mode and navigating within the panel.

Browsers may cache the manifest and installed application metadata. After changing the name, scope, display mode, or icons, increment the cache version, clear the site's application data, and reinstall the PWA when necessary.

## Updating

After updating the package, publish new migrations and assets, run migrations, and refresh Filament's assets:

```bash
php artisan vendor:publish --tag="filament-pwa-plugin-migrations"
php artisan vendor:publish --tag="filament-pwa-plugin-assets" --force
php artisan migrate
php artisan filament:assets
php artisan optimize:clear
```

Do not use `--force` when publishing the configuration unless replacing local configuration changes is intentional.

## Testing

```bash
composer test
composer analyse
composer test:lint
composer test:refactor
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for information about recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security vulnerabilities

Please review [our security policy](.github/SECURITY.md) to report security vulnerabilities.

## Credits

- [Eduardo Ismalej](https://github.com/edu17)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
