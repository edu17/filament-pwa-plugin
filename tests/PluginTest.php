<?php

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Edu17\FilamentPwaPlugin\FilamentPwaPluginServiceProvider;
use Edu17\FilamentPwaPlugin\Support\IconGenerator;
use Edu17\FilamentPwaPlugin\Support\PwaSettingsRepository;
use Filament\Facades\Filament;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

it('registers its install command', function () {
    $this->artisan('help', ['command_name' => 'filament-pwa-plugin:install'])
        ->assertSuccessful();
});

it('publishes configuration, public assets, and the settings migration', function () {
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
        ->and($migrations)->toHaveCount(1)
        ->and(realpath(array_key_first($migrations)))->toBe(realpath(__DIR__ . '/../database/migrations/create_filament_pwa_settings_table.php.stub'));
});

it('stores manifest settings independently for each panel', function () {
    config()->set('pwa-plugin.settings.enabled', true);

    Schema::create('filament_pwa_settings', function (Blueprint $table): void {
        $table->id();
        $table->string('panel_id')->unique();
        $table->json('data');
        $table->string('icon_path')->nullable();
        $table->timestamps();
    });

    app(PwaSettingsRepository::class)->save('admin', [
        'name' => 'Stored Admin',
        'theme_color' => '#123456',
    ], 'admin/source/icon.png');

    $adminPanel = Filament::getPanel('admin');
    $adminPlugin = $adminPanel->getPlugin(FilamentPwaPlugin::ID);
    $portalPanel = Filament::getPanel('portal');
    $portalPlugin = $portalPanel->getPlugin(FilamentPwaPlugin::ID);

    expect($adminPlugin)->toBeInstanceOf(FilamentPwaPlugin::class)
        ->and($adminPlugin->getName($adminPanel))->toBe('Stored Admin')
        ->and($adminPlugin->getThemeColor($adminPanel))->toBe('#123456')
        ->and($portalPlugin)->toBeInstanceOf(FilamentPwaPlugin::class)
        ->and($portalPlugin->getName($portalPanel))->toBe(config('app.name'));
});

it('can expose settings by panel authentication without requiring roles', function () {
    $panel = Filament::getPanel('admin');
    $plugin = FilamentPwaPlugin::make()
        ->settingsPage()
        ->managePanels(['admin']);

    expect($plugin->hasSettingsPage($panel))->toBeTrue()
        ->and($plugin->getManageablePanelIds($panel))->toBe(['admin'])
        ->and($plugin->canManageSettings($panel))->toBeFalse();

    Auth::guard($panel->getAuthGuard())->setUser(new GenericUser(['id' => 1]));

    expect($plugin->canManageSettings($panel))->toBeTrue()
        ->and($plugin->authorizeSettingsUsing(fn (): bool => false)->canManageSettings($panel))->toBeFalse();
});

it('generates all PWA icon variants from one square image', function () {
    Storage::fake('public');
    config()->set('pwa-plugin.settings.disk', 'public');
    Storage::disk('public')->put(
        'source.png',
        file_get_contents(__DIR__ . '/../resources/dist/icons/icon-512x512.png'),
    );

    $icons = app(IconGenerator::class)->generate('admin', 'source.png', '#ffffff');

    expect($icons)->toHaveCount(4)
        ->and($icons[0]['sizes'])->toBe('192x192')
        ->and($icons[1]['sizes'])->toBe('512x512')
        ->and($icons[2]['purpose'])->toBe('maskable')
        ->and($icons[3]['sizes'])->toBe('180x180');

    Storage::disk('public')->assertExists([
        'filament-pwa-plugin/admin/icons/icon-192x192.png',
        'filament-pwa-plugin/admin/icons/icon-512x512.png',
        'filament-pwa-plugin/admin/icons/icon-maskable-512x512.png',
        'filament-pwa-plugin/admin/icons/apple-touch-icon.png',
    ]);
});

it('generates an installable manifest for the registered panel', function () {
    $response = $this->get('/admin/manifest.webmanifest')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('id', '/admin')
        ->assertJsonPath('name', 'Acme Control Center')
        ->assertJsonPath('short_name', 'Acme Admin')
        ->assertJsonPath('description', 'Manage the Acme application.')
        ->assertJsonPath('start_url', '/admin')
        ->assertJsonPath('scope', '/admin')
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
        ->assertJsonPath('id', '/portal')
        ->assertJsonPath('scope', '/portal')
        ->assertJsonPath('name', config('app.name'))
        ->assertJsonMissingExact([
            'name' => 'Acme Control Center',
        ]);
});

it('serves a secure offline-first service worker', function () {
    $response = $this->get('/admin/pwa-service-worker.js')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertHeader('Service-Worker-Allowed', '/admin')
        ->assertSee("request.method !== 'GET'", false)
        ->assertSee("request.mode === 'navigate'", false)
        ->assertSee("['script', 'style', 'font']", false)
        ->assertSee('url.pathname === APP_SCOPE', false)
        ->assertSee('url.pathname.startsWith(`${APP_SCOPE}/`)', false)
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
        ->toContain('content="/admin"')
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
        ->and($plugin->getScope($panel))->toBe('/custom')
        ->and($plugin->getManifestId($panel))->toBe('/custom-app');
});

it('can be disabled globally or for an individual panel', function () {
    $panel = Filament::getPanel('portal');

    config()->set('pwa-plugin.enabled', false);

    expect(FilamentPwaPlugin::make()->isEnabled($panel))->toBeFalse()
        ->and(FilamentPwaPlugin::make()->enabled()->isEnabled($panel))->toBeTrue()
        ->and(FilamentPwaPlugin::make()->enabled(fn (): bool => false)->isEnabled($panel))->toBeFalse();
});
