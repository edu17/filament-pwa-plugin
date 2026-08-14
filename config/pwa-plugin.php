<?php

return [
    'enabled' => true,

    'manifest' => [
        'name' => config('app.name', 'Filament'),
        'short_name' => config('app.name', 'Filament'),
        'description' => null,
        'id' => null,
        'start_url' => null,
        'scope' => null,
        'display' => 'standalone',
        'orientation' => 'any',
        'theme_color' => '#18181b',
        'background_color' => '#ffffff',
    ],

    'icons' => [
        [
            'src' => 'vendor/filament-pwa-plugin/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => 'vendor/filament-pwa-plugin/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => 'vendor/filament-pwa-plugin/icons/icon-maskable-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
    ],

    'apple_touch_icon' => 'vendor/filament-pwa-plugin/icons/apple-touch-icon.png',

    'offline' => [
        'cache_version' => '1',
        'precache' => [],
        'title' => 'You are offline',
        'message' => 'Check your internet connection and try again.',
        'retry_label' => 'Try again',
    ],
];
