<?php

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Edu17\FilamentPwaPlugin\FilamentPwaPluginServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

it('registers its install command', function () {
    $this->artisan('help', ['command_name' => 'filament-pwa-plugin:install'])
        ->assertSuccessful();
});

it('publishes configuration and public assets without migrations', function () {
    $config = ServiceProvider::pathsToPublish(
        FilamentPwaPluginServiceProvider::class,
        'filament-pwa-plugin-config',
    );
    $assets = ServiceProvider::pathsToPublish(
        FilamentPwaPluginServiceProvider::class,
        'filament-pwa-plugin-assets',
    );
    $migrations = ServiceProvider::pathsToPublish(
        FilamentPwaPluginServiceProvider::class,
        'filament-pwa-plugin-migrations',
    );

    expect($config)->toHaveCount(1)
        ->and(realpath(array_key_first($config)))->toBe(realpath(__DIR__ . '/../config/pwa-plugin.php'))
        ->and($assets)->toHaveCount(1)
        ->and(realpath(array_key_first($assets)))->toBe(realpath(__DIR__ . '/../resources/dist'))
        ->and($migrations)->toBe([]);
});

it('generates an installable manifest for the registered panel', function () {
    $response = $this->get('/admin/manifest.webmanifest')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('id', '/admin/')
        ->assertJsonPath('name', 'Acme Control Center')
        ->assertJsonPath('short_name', 'Acme Admin')
        ->assertJsonPath('description', 'Manage the Acme application.')
        ->assertJsonPath('start_url', '/admin/')
        ->assertJsonPath('scope', '/admin/')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('theme_color', '#f59e0b')
        ->assertJsonPath('background_color', '#fafafa')
        ->assertJsonCount(3, 'icons')
        ->assertJsonPath('icons.0.sizes', '192x192')
        ->assertJsonPath('icons.1.sizes', '512x512')
        ->assertJsonPath('icons.2.purpose', 'maskable');

    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=3600');
});

it('isolates manifest scope and defaults between panels', function () {
    $this->get('/portal/manifest.webmanifest')
        ->assertSuccessful()
        ->assertJsonPath('id', '/portal/')
        ->assertJsonPath('scope', '/portal/')
        ->assertJsonPath('name', config('app.name'))
        ->assertJsonMissingExact([
            'name' => 'Acme Control Center',
        ]);
});

it('serves a secure offline-first service worker', function () {
    $response = $this->get('/admin/pwa-service-worker.js')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertHeader('Service-Worker-Allowed', '/admin/')
        ->assertSee("request.method !== 'GET'", false)
        ->assertSee("request.mode === 'navigate'", false)
        ->assertSee("['script', 'style', 'font']", false)
        ->assertSee('caches.match(OFFLINE_URL)', false)
        ->assertDontSee("'image'", false);

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-cache')
        ->toContain('no-store')
        ->toContain('must-revalidate');
});

it('serves a standalone offline fallback', function () {
    $this->get('/admin/offline')
        ->assertSuccessful()
        ->assertSee('Acme Control Center')
        ->assertSee(config('pwa-plugin.offline.title'))
        ->assertSee(config('pwa-plugin.offline.retry_label'));
});

it('renders panel-specific PWA metadata', function () {
    $panel = Filament::getPanel('admin');
    $plugin = $panel->getPlugin(FilamentPwaPlugin::ID);

    $html = view('filament-pwa-plugin::head', compact('panel', 'plugin'))->render();

    expect($html)
        ->toContain('rel="manifest"')
        ->toContain('/admin/manifest.webmanifest')
        ->toContain('/admin/pwa-service-worker.js')
        ->toContain('content="/admin/"')
        ->toContain('content="#f59e0b"')
        ->toContain('apple-touch-icon');
});

it('supports fluent values and closures per panel', function () {
    $panel = Filament::getPanel('portal');
    $plugin = FilamentPwaPlugin::make()
        ->name(fn ($currentPanel): string => "{$currentPanel->getId()} app")
        ->scope('/custom')
        ->manifestId('/custom-app');

    expect($plugin->getName($panel))->toBe('portal app')
        ->and($plugin->getScope($panel))->toBe('/custom/')
        ->and($plugin->getManifestId($panel))->toBe('/custom-app');
});

it('can be disabled globally or for an individual panel', function () {
    $panel = Filament::getPanel('portal');

    config()->set('pwa-plugin.enabled', false);

    expect(FilamentPwaPlugin::make()->isEnabled($panel))->toBeFalse()
        ->and(FilamentPwaPlugin::make()->enabled()->isEnabled($panel))->toBeTrue()
        ->and(FilamentPwaPlugin::make()->enabled(fn (): bool => false)->isEnabled($panel))->toBeFalse();
});
