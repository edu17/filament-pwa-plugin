<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Support;

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Filament\Panel;

class Manifest
{
    public function __construct(
        protected FilamentPwaPlugin $plugin,
        protected Panel $panel,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->plugin->getManifestId($this->panel),
            'name' => $this->plugin->getName($this->panel),
            'short_name' => $this->plugin->getShortName($this->panel),
            'description' => $this->plugin->getDescription($this->panel),
            'start_url' => $this->plugin->getStartUrl($this->panel),
            'scope' => $this->plugin->getScope($this->panel),
            'display' => $this->plugin->getDisplay($this->panel),
            'orientation' => $this->plugin->getOrientation($this->panel),
            'theme_color' => $this->plugin->getThemeColor($this->panel),
            'background_color' => $this->plugin->getBackgroundColor($this->panel),
            'icons' => $this->icons(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @return array<int, array<string, string>> */
    protected function icons(): array
    {
        return array_map(function (array $icon): array {
            $icon['src'] = $this->plugin->resolveAssetUrl($icon['src']);

            return $icon;
        }, $this->plugin->getIcons($this->panel));
    }
}
