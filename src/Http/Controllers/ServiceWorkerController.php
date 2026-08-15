<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Http\Controllers;

use Edu17\FilamentPwaPlugin\Support\ResolvesPanelPlugin;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ServiceWorkerController
{
    use ResolvesPanelPlugin;

    public function __invoke(string $pwaPanel): Response
    {
        [$panel, $plugin] = $this->resolvePanelPlugin($pwaPanel);

        $offlineUrl = $panel->route('pwa.offline');
        $iconUrls = array_map(
            fn (array $icon): string => $plugin->resolveAssetUrl($icon['src']),
            $plugin->getIcons($panel),
        );

        $cachePrefix = Str::slug(config('app.name', 'filament')) . '-' . $panel->getId() . '-pwa';
        $precacheUrls = array_values(array_unique([
            $offlineUrl,
            ...$iconUrls,
            ...array_map(
                $plugin->resolveAssetUrl(...),
                $plugin->getPrecacheUrls(),
            ),
        ]));

        return response()
            ->view('filament-pwa-plugin::service-worker', [
                'appScope' => $plugin->getScope($panel),
                'cacheName' => "{$cachePrefix}-{$plugin->getCacheVersion($panel)}",
                'cachePrefix' => $cachePrefix,
                'offlineUrl' => $offlineUrl,
                'precacheUrls' => $precacheUrls,
            ])
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Service-Worker-Allowed', $plugin->getScope($panel));
    }
}
