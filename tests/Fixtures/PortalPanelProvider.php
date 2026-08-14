<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Tests\Fixtures;

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->brandName('Customer Portal')
            ->plugin(FilamentPwaPlugin::make());
    }
}
