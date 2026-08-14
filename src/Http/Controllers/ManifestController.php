<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Http\Controllers;

use Edu17\FilamentPwaPlugin\Support\Manifest;
use Edu17\FilamentPwaPlugin\Support\ResolvesPanelPlugin;
use Illuminate\Http\JsonResponse;

class ManifestController
{
    use ResolvesPanelPlugin;

    public function __invoke(string $pwaPanel): JsonResponse
    {
        [$panel, $plugin] = $this->resolvePanelPlugin($pwaPanel);

        return response()
            ->json((new Manifest($plugin, $panel))->toArray())
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
