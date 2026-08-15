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
            'message' => $plugin->getOfflineMessage($panel),
            'name' => $plugin->getName($panel),
            'retryLabel' => $plugin->getOfflineRetryLabel($panel),
            'themeColor' => $plugin->getThemeColor($panel),
            'title' => $plugin->getOfflineTitle($panel),
        ]);
    }
}
