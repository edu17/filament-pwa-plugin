<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Support;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use RuntimeException;

class IconGenerator
{
    /** @return array<int, array<string, string>> */
    public function generate(string $panelId, string $sourcePath, string $backgroundColor): array
    {
        $disk = Storage::disk(config('pwa-plugin.settings.disk', 'public'));
        $source = $disk->get($sourcePath);
        $manager = $this->manager();
        $directory = trim((string) config('pwa-plugin.settings.directory', 'filament-pwa-plugin'), '/');
        $directory = "{$directory}/{$panelId}/icons";

        $paths = [
            '192x192' => "{$directory}/icon-192x192.png",
            '512x512' => "{$directory}/icon-512x512.png",
            '180x180' => "{$directory}/apple-touch-icon.png",
            'maskable' => "{$directory}/icon-maskable-512x512.png",
        ];

        foreach ([192, 512, 180] as $size) {
            $disk->put(
                $paths["{$size}x{$size}"],
                (string) $manager->read($source)->cover($size, $size)->toPng(),
            );
        }

        $foreground = $manager->read($source)->cover(384, 384);
        $maskable = $manager->create(512, 512)->fill($backgroundColor)->place($foreground, 'center');
        $disk->put($paths['maskable'], (string) $maskable->toPng());

        return [
            $this->icon($disk->url($paths['192x192']), '192x192', 'any'),
            $this->icon($disk->url($paths['512x512']), '512x512', 'any'),
            $this->icon($disk->url($paths['maskable']), '512x512', 'maskable'),
            $this->icon($disk->url($paths['180x180']), '180x180', 'any'),
        ];
    }

    protected function manager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            return ImageManager::imagick(strip: true);
        }

        if (extension_loaded('gd')) {
            return ImageManager::gd(strip: true);
        }

        throw new RuntimeException('Generating PWA icons requires the GD or Imagick PHP extension.');
    }

    /** @return array<string, string> */
    protected function icon(string $src, string $sizes, string $purpose): array
    {
        return [
            'src' => $src,
            'sizes' => $sizes,
            'type' => 'image/png',
            'purpose' => $purpose,
        ];
    }
}
