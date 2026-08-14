<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Http\Controllers;

use Edu17\FilamentPwaPlugin\Support\ResolvesPanelPlugin;
use Illuminate\Contracts\View\View;

class OfflineController
{
    use ResolvesPanelPlugin;

    public function __invoke(string $pwaPanel): View
    {
        [$panel, $plugin] = $this->resolvePanelPlugin($pwaPanel);

        return view('filament-pwa-plugin::offline', [
            'backgroundColor' => $plugin->getBackgroundColor($panel),
            'message' => config('pwa-plugin.offline.message'),
            'name' => $plugin->getName($panel),
            'retryLabel' => config('pwa-plugin.offline.retry_label'),
            'themeColor' => $plugin->getThemeColor($panel),
            'title' => config('pwa-plugin.offline.title'),
        ]);
    }
}
