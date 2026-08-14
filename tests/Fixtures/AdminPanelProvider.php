<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Tests\Fixtures;

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Acme Administration')
            ->plugin(
                FilamentPwaPlugin::make()
                    ->name('Acme Control Center')
                    ->shortName('Acme Admin')
                    ->description('Manage the Acme application.')
                    ->themeColor('#f59e0b')
                    ->backgroundColor('#fafafa'),
            );
    }
}
