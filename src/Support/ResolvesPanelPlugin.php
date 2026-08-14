<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Support;

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ResolvesPanelPlugin
{
    /** @return array{Panel, FilamentPwaPlugin} */
    protected function resolvePanelPlugin(string $panelId): array
    {
        $panel = Filament::getPanel($panelId);

        if (! $panel->hasPlugin(FilamentPwaPlugin::ID)) {
            throw new NotFoundHttpException;
        }

        $plugin = $panel->getPlugin(FilamentPwaPlugin::ID);

        if (! $plugin instanceof FilamentPwaPlugin) {
            throw new NotFoundHttpException;
        }

        return [$panel, $plugin];
    }
}
