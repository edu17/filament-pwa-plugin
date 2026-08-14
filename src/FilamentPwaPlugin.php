<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin;

use Closure;
use Edu17\FilamentPwaPlugin\Http\Controllers\ManifestController;
use Edu17\FilamentPwaPlugin\Http\Controllers\OfflineController;
use Edu17\FilamentPwaPlugin\Http\Controllers\ServiceWorkerController;
use Filament\Contracts\Plugin;
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

    /** @var array<int, array<string, string>> | Closure | null */
    protected array | Closure | null $icons = null;

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if (! $this->isEnabled($panel)) {
            return;
        }

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
        $name = $this->evaluate($this->name, $panel)
            ?? config('pwa-plugin.manifest.name')
            ?? $panel->getBrandName();

        if ($name instanceof Htmlable) {
            $name = $name->toHtml();
        }

        return trim(strip_tags((string) $name));
    }

    public function getShortName(Panel $panel): string
    {
        return (string) ($this->evaluate($this->shortName, $panel)
            ?? config('pwa-plugin.manifest.short_name')
            ?? $this->getName($panel));
    }

    public function getDescription(Panel $panel): ?string
    {
        return $this->evaluate($this->description, $panel)
            ?? config('pwa-plugin.manifest.description');
    }

    public function getThemeColor(Panel $panel): string
    {
        return (string) ($this->evaluate($this->themeColor, $panel)
            ?? config('pwa-plugin.manifest.theme_color', '#18181b'));
    }

    public function getBackgroundColor(Panel $panel): string
    {
        return (string) ($this->evaluate($this->backgroundColor, $panel)
            ?? config('pwa-plugin.manifest.background_color', '#ffffff'));
    }

    public function getDisplay(Panel $panel): string
    {
        return (string) ($this->evaluate($this->display, $panel)
            ?? config('pwa-plugin.manifest.display', 'standalone'));
    }

    public function getOrientation(Panel $panel): ?string
    {
        return $this->evaluate($this->orientation, $panel)
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
        $icons = $this->evaluate($this->icons, $panel)
            ?? config('pwa-plugin.icons', []);

        return $icons;
    }

    public function getAppleTouchIcon(Panel $panel): ?string
    {
        $icon = config('pwa-plugin.apple_touch_icon');

        if (filled($icon)) {
            return $this->resolveAssetUrl((string) $icon);
        }

        foreach ($this->getIcons($panel) as $candidate) {
            if (($candidate['sizes'] ?? null) === '180x180') {
                return $this->resolveAssetUrl($candidate['src']);
            }
        }

        return null;
    }

    public function getCacheVersion(): string
    {
        return (string) config('pwa-plugin.offline.cache_version', '1');
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

    protected function evaluate(mixed $value, Panel $panel): mixed
    {
        return $value instanceof Closure ? $value($panel) : $value;
    }

    protected function normalizeScope(string $scope): string
    {
        $scope = '/' . trim($scope, '/');

        return $scope === '/' ? $scope : "{$scope}/";
    }
}
