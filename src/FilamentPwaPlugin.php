<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin;

use Closure;
use Edu17\FilamentPwaPlugin\Http\Controllers\ManifestController;
use Edu17\FilamentPwaPlugin\Http\Controllers\OfflineController;
use Edu17\FilamentPwaPlugin\Http\Controllers\ServiceWorkerController;
use Edu17\FilamentPwaPlugin\Pages\ManagePwaSettings;
use Edu17\FilamentPwaPlugin\Support\PwaSettingsRepository;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class FilamentPwaPlugin implements Plugin
{
    public const ID = 'filament-pwa-plugin';

    protected bool | Closure | null $isEnabled = null;

    protected string | Closure | null $name = null;

    protected string | Closure | null $shortName = null;

    protected string | Closure | null $description = null;

    protected string | Closure | null $themeColor = null;

    protected string | Closure | null $backgroundColor = null;

    protected string | Closure | null $display = null;

    protected string | Closure | null $orientation = null;

    protected string | Closure | null $startUrl = null;

    protected string | Closure | null $scope = null;

    protected string | Closure | null $manifestId = null;

    protected bool | Closure | null $hasSettingsPage = null;

    /** @var array<int, string> | Closure | null */
    protected array | Closure | null $manageablePanels = null;

    protected ?Closure $settingsAuthorization = null;

    /** @var array<int, array<string, string>> | Closure | null */
    protected array | Closure | null $icons = null;

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if ($this->hasSettingsPage($panel)) {
            $panel->pages([ManagePwaSettings::class]);
        }

        if ($this->isEnabled($panel)) {
            $panel
                ->routes(function (Panel $panel): void {
                    Route::get('/manifest.webmanifest', ManifestController::class)
                        ->defaults('pwaPanel', $panel->getId())
                        ->name('pwa.manifest');

                    Route::get('/pwa-service-worker.js', ServiceWorkerController::class)
                        ->defaults('pwaPanel', $panel->getId())
                        ->name('pwa.service-worker');

                    Route::get('/offline', OfflineController::class)
                        ->defaults('pwaPanel', $panel->getId())
                        ->name('pwa.offline');
                })
                ->renderHook(
                    PanelsRenderHook::HEAD_END,
                    fn (): View => view('filament-pwa-plugin::head', [
                        'panel' => $panel,
                        'plugin' => $this,
                    ]),
                );
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament()->getCurrentPanel()?->getPlugin(self::ID)
            ?? throw new \LogicException('The Filament PWA plugin is not registered on the current panel.');

        return $plugin;
    }

    public function enabled(bool | Closure $condition = true): static
    {
        $this->isEnabled = $condition;

        return $this;
    }

    public function name(string | Closure | null $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function shortName(string | Closure | null $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function description(string | Closure | null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function themeColor(string | Closure | null $color): static
    {
        $this->themeColor = $color;

        return $this;
    }

    public function backgroundColor(string | Closure | null $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    public function display(string | Closure | null $display): static
    {
        $this->display = $display;

        return $this;
    }

    public function orientation(string | Closure | null $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function startUrl(string | Closure | null $url): static
    {
        $this->startUrl = $url;

        return $this;
    }

    public function scope(string | Closure | null $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function manifestId(string | Closure | null $id): static
    {
        $this->manifestId = $id;

        return $this;
    }

    public function settingsPage(bool | Closure $condition = true): static
    {
        $this->hasSettingsPage = $condition;

        return $this;
    }

    /** @param array<int, string> | Closure $panels */
    public function managePanels(array | Closure $panels): static
    {
        $this->manageablePanels = $panels;

        return $this;
    }

    public function authorizeSettingsUsing(?Closure $callback): static
    {
        $this->settingsAuthorization = $callback;

        return $this;
    }

    /** @param array<int, array<string, string>> | Closure | null $icons */
    public function icons(array | Closure | null $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    public function isEnabled(Panel $panel): bool
    {
        return (bool) ($this->evaluate($this->isEnabled, $panel)
            ?? config('pwa-plugin.enabled', true));
    }

    public function getName(Panel $panel): string
    {
        $name = $this->storedValue($panel, 'name')
            ?? $this->evaluate($this->name, $panel)
            ?? config('pwa-plugin.manifest.name')
            ?? $panel->getBrandName();

        if ($name instanceof Htmlable) {
            $name = $name->toHtml();
        }

        return trim(strip_tags((string) $name));
    }

    public function getShortName(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'short_name')
            ?? $this->evaluate($this->shortName, $panel)
            ?? config('pwa-plugin.manifest.short_name')
            ?? $this->getName($panel));
    }

    public function getDescription(Panel $panel): ?string
    {
        return $this->storedValue($panel, 'description')
            ?? $this->evaluate($this->description, $panel)
            ?? config('pwa-plugin.manifest.description');
    }

    public function getThemeColor(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'theme_color')
            ?? $this->evaluate($this->themeColor, $panel)
            ?? config('pwa-plugin.manifest.theme_color', '#18181b'));
    }

    public function getBackgroundColor(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'background_color')
            ?? $this->evaluate($this->backgroundColor, $panel)
            ?? config('pwa-plugin.manifest.background_color', '#ffffff'));
    }

    public function getDisplay(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'display')
            ?? $this->evaluate($this->display, $panel)
            ?? config('pwa-plugin.manifest.display', 'standalone'));
    }

    public function getOrientation(Panel $panel): ?string
    {
        return $this->storedValue($panel, 'orientation')
            ?? $this->evaluate($this->orientation, $panel)
            ?? config('pwa-plugin.manifest.orientation');
    }

    public function getScope(Panel $panel): string
    {
        $scope = $this->evaluate($this->scope, $panel)
            ?? config('pwa-plugin.manifest.scope');

        if (filled($scope)) {
            return $this->normalizeScope((string) $scope);
        }

        return $this->normalizeScope('/' . trim($panel->getPath(), '/'));
    }

    public function getStartUrl(Panel $panel): string
    {
        return (string) ($this->evaluate($this->startUrl, $panel)
            ?? config('pwa-plugin.manifest.start_url')
            ?? $this->getScope($panel));
    }

    public function getManifestId(Panel $panel): string
    {
        return (string) ($this->evaluate($this->manifestId, $panel)
            ?? config('pwa-plugin.manifest.id')
            ?? $this->getScope($panel));
    }

    /** @return array<int, array<string, string>> */
    public function getIcons(Panel $panel): array
    {
        /** @var array<int, array<string, string>> $icons */
        $icons = $this->storedValue($panel, 'icons')
            ?? $this->evaluate($this->icons, $panel)
            ?? config('pwa-plugin.icons', []);

        return $icons;
    }

    public function getAppleTouchIcon(Panel $panel): ?string
    {
        foreach ($this->getIcons($panel) as $candidate) {
            if (($candidate['sizes'] ?? null) === '180x180') {
                return $this->resolveAssetUrl($candidate['src']);
            }
        }

        $icon = config('pwa-plugin.apple_touch_icon');

        return filled($icon) ? $this->resolveAssetUrl((string) $icon) : null;
    }

    public function getOfflineTitle(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'offline_title')
            ?? config('pwa-plugin.offline.title', 'You are offline'));
    }

    public function getOfflineMessage(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'offline_message')
            ?? config('pwa-plugin.offline.message', 'Check your internet connection and try again.'));
    }

    public function getOfflineRetryLabel(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'offline_retry_label')
            ?? config('pwa-plugin.offline.retry_label', 'Try again'));
    }

    public function getCacheVersion(Panel $panel): string
    {
        return (string) ($this->storedValue($panel, 'cache_version')
            ?? config('pwa-plugin.offline.cache_version', '1'));
    }

    /** @return array<int, string> */
    public function getPrecacheUrls(): array
    {
        return config('pwa-plugin.offline.precache', []);
    }

    public function resolveAssetUrl(string $url): string
    {
        if (str_starts_with($url, '/') || str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            return $url;
        }

        return asset($url);
    }

    public function hasSettingsPage(Panel $panel): bool
    {
        if ($this->hasSettingsPage !== null) {
            return (bool) $this->evaluate($this->hasSettingsPage, $panel);
        }

        return (bool) config('pwa-plugin.settings.enabled', false)
            && in_array($panel->getId(), config('pwa-plugin.settings.navigation_panels', []), true);
    }

    /** @return array<int, string> */
    public function getManageablePanelIds(Panel $panel): array
    {
        /** @var array<int, string> $panels */
        $panels = $this->evaluate($this->manageablePanels, $panel)
            ?? config('pwa-plugin.settings.manageable_panels', []);

        return $panels;
    }

    public function canManageSettings(Panel $panel): bool
    {
        if ($this->settingsAuthorization) {
            return (bool) ($this->settingsAuthorization)(Filament::auth()->user(), $panel);
        }

        if (filled($ability = config('pwa-plugin.settings.ability'))) {
            return Filament::auth()->user()?->can((string) $ability) === true;
        }

        return Filament::auth()->check();
    }

    protected function evaluate(mixed $value, Panel $panel): mixed
    {
        return $value instanceof Closure ? $value($panel) : $value;
    }

    protected function storedValue(Panel $panel, string $key): mixed
    {
        if (! config('pwa-plugin.settings.enabled', false)) {
            return null;
        }

        return app(PwaSettingsRepository::class)->value($panel->getId(), $key);
    }

    protected function normalizeScope(string $scope): string
    {
        return '/' . trim($scope, '/');
    }
}
