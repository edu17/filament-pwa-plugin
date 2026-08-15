# Filament PWA Plugin

[![Latest Version on Packagist](https://img.shields.io/packagist/v/edu17/filament-pwa-plugin.svg?style=flat-square)](https://packagist.org/packages/edu17/filament-pwa-plugin)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/edu17/filament-pwa-plugin/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/edu17/filament-pwa-plugin/actions/workflows/tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/edu17/filament-pwa-plugin/fix-code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/edu17/filament-pwa-plugin/actions/workflows/fix-code-style.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/edu17/filament-pwa-plugin.svg?style=flat-square)](https://packagist.org/packages/edu17/filament-pwa-plugin)

Turn any FilamentPHP 4 panel into an installable Progressive Web App with an isolated manifest, service worker, offline fallback, and per-panel configuration.

## Installation

Install the package with Composer:

```bash
composer require edu17/filament-pwa-plugin
```

Publish the configuration, migration, and PWA icons, run the migration, then install the Filament assets:

```bash
php artisan filament-pwa-plugin:install
```

Register the plugin in each panel that should become a PWA:

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

Service workers require HTTPS in production. Localhost is allowed for local development.

If uploaded icons are stored on the default `public` disk, make sure the application has its storage link:

```bash
php artisan storage:link
```

## Configuration

Publish the configuration manually when needed:

```bash
php artisan vendor:publish --tag="filament-pwa-plugin-config"
```

The published `config/pwa-plugin.php` file controls the global defaults:

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

    // ...
];
```

You can override manifest values for an individual panel with the fluent API:

```php
FilamentPwaPlugin::make()
    ->name('Administration')
    ->shortName('Admin')
    ->description('Manage the application.')
    ->themeColor('#f59e0b')
    ->backgroundColor('#ffffff')
    ->display('standalone')
    ->orientation('any');
```

Every option also accepts a closure that receives the current `Panel`:

```php
FilamentPwaPlugin::make()
    ->name(fn (Panel $panel): string => "{$panel->getId()} app")
    ->enabled(fn (Panel $panel): bool => $panel->getId() !== 'internal');
```

## Settings page

The optional settings page stores manifest, offline, and icon values independently for each managed panel. Enable it in the configuration, select where its navigation should appear, and list the panels it may configure:

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
FilamentPwaPlugin::make()
    ->enabled(false);

// AdminPanelProvider.php
FilamentPwaPlugin::make()
    ->name('My application');
```

No roles package is required. By default, any authenticated user with access to the panel containing the settings page may use it. Applications with permissions can set a Gate ability in the same configuration:

```php
'ability' => 'manage-pwa-settings',
```

For custom authorization, override the settings-page registration with a callback instead:

```php
FilamentPwaPlugin::make()
    ->settingsPage()
    ->managePanels(['admin'])
    ->authorizeSettingsUsing(
        fn ($user, Panel $panel): bool => $user?->is_super_admin === true,
    );
```

The page accepts one square source image of at least 512×512 pixels and generates 192×192, 512×512, maskable 512×512, and Apple 180×180 PNG files. GD or Imagick must be available in PHP.

## Icons

The installation command publishes default icons to `public/vendor/filament-pwa-plugin/icons`. Replace them with your application's branded PNG files or configure custom URLs in `config/pwa-plugin.php`.

The default manifest includes:

- 192×192 icon.
- 512×512 icon.
- 512×512 maskable icon.
- 180×180 Apple touch icon.

Republish package assets after an upgrade with:

```bash
php artisan vendor:publish --tag="filament-pwa-plugin-assets" --force
php artisan filament:assets
```

## Offline behavior

The service worker is scoped to the panel path. For example, a panel at `/admin` receives an `/admin` scope and does not handle requests outside `/admin` or `/admin/...`.

The safe default strategy:

- Precaches the offline fallback and local PWA icons.
- Uses the network for all page navigations.
- Shows the offline fallback when a navigation fails.
- Runtime-caches same-origin scripts, styles, and fonts.
- Never intercepts non-GET requests or cross-origin requests.
- Does not cache authenticated HTML, Livewire responses, APIs, or uploaded images.

Increment `offline.cache_version` after changing cached resources. Extra public URLs may be added to `offline.precache`.

## Testing

```bash
composer test
composer analyse
composer test:lint
composer test:refactor
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Eduardo Ismalej](https://github.com/edu17)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
