<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPwaPluginServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-pwa-plugin';

    public static string $viewNamespace = 'filament-pwa-plugin';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('filament:assets');
                    })
                    ->askToStarRepoOnGitHub('edu17/filament-pwa-plugin');
            });

        if (file_exists($package->basePath('/../config/pwa-plugin.php'))) {
            $package->hasConfigFile('pwa-plugin');
        }

        if (file_exists($package->basePath('/../resources/dist'))) {
            $package->hasAssets();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName(),
        );
    }

    protected function getAssetPackageName(): string
    {
        return 'edu17/filament-pwa-plugin';
    }

    /** @return array<Asset> */
    protected function getAssets(): array
    {
        return [
            Js::make(
                'registration',
                __DIR__ . '/../resources/dist/filament-pwa-plugin.js',
            )->defer(),
        ];
    }
}
