<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Pages;

use Edu17\FilamentPwaPlugin\FilamentPwaPlugin;
use Edu17\FilamentPwaPlugin\Support\IconGenerator;
use Edu17\FilamentPwaPlugin\Support\PwaSettingsRepository;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManagePwaSettings extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'PWA';

    protected static ?string $title = 'Progressive Web App';

    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';

    protected static ?string $slug = 'pwa-settings';

    protected string $view = 'filament-pwa-plugin::pages.manage-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $panelId = array_key_first($this->panelOptions());

        abort_unless($panelId, 404);

        $this->loadPanel($panelId);
    }

    public static function canAccess(): bool
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel?->hasPlugin(FilamentPwaPlugin::ID)) {
            return false;
        }

        $plugin = $panel->getPlugin(FilamentPwaPlugin::ID);

        return $plugin instanceof FilamentPwaPlugin && $plugin->canManageSettings($panel);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Panel administrado')
                    ->schema([
                        Select::make('panel_id')
                            ->label('Panel')
                            ->options($this->panelOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (string $state) => $this->loadPanel($state)),
                    ]),
                Section::make('Identidad')
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                        TextInput::make('short_name')->label('Nombre corto')->required()->maxLength(32),
                        Textarea::make('description')->label('Descripción')->rows(3)->maxLength(500),
                        ColorPicker::make('theme_color')->label('Color principal')->required(),
                        ColorPicker::make('background_color')->label('Color de fondo')->required(),
                        Select::make('display')->options([
                            'standalone' => 'Standalone',
                            'minimal-ui' => 'Minimal UI',
                            'fullscreen' => 'Fullscreen',
                        ])->required(),
                        Select::make('orientation')->options([
                            'any' => 'Automática',
                            'portrait' => 'Vertical',
                            'landscape' => 'Horizontal',
                        ])->required(),
                    ])->columns(2),
                Section::make('Icono')
                    ->description('Sube una imagen cuadrada de al menos 512×512 px. El paquete generará todos los tamaños necesarios.')
                    ->schema([
                        FileUpload::make('icon_path')
                            ->label('Imagen maestra')
                            ->disk(config('pwa-plugin.settings.disk', 'public'))
                            ->directory(fn (): string => trim((string) config('pwa-plugin.settings.directory', 'filament-pwa-plugin'), '/') . "/{$this->data['panel_id']}/source")
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions(['1:1'])
                            ->rules(['dimensions:min_width=512,min_height=512,ratio=1/1'])
                            ->maxSize(5120),
                    ]),
                Section::make('Experiencia offline')
                    ->schema([
                        TextInput::make('offline_title')->label('Título')->required(),
                        Textarea::make('offline_message')->label('Mensaje')->required(),
                        TextInput::make('offline_retry_label')->label('Texto del botón')->required(),
                        TextInput::make('cache_version')->label('Versión de caché')->required(),
                    ])->columns(2),
            ]);
    }

    public function save(PwaSettingsRepository $settings, IconGenerator $icons): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->getFormSchema()->getState();
        $panelId = (string) $state['panel_id'];
        abort_unless(array_key_exists($panelId, $this->panelOptions()), 403);

        $iconPath = $state['icon_path'] ?: null;
        unset($state['panel_id'], $state['icon_path']);

        $existing = $settings->find($panelId);
        if ($iconPath && (($iconPath !== $existing?->icon_path) || blank($existing?->data['icons'] ?? null))) {
            $state['icons'] = $icons->generate($panelId, $iconPath, (string) $state['background_color']);
        } elseif (filled($existing?->data['icons'] ?? null)) {
            $state['icons'] = $existing->data['icons'];
        }

        $settings->save($panelId, $state, $iconPath);

        Notification::make()->success()->title('Configuración PWA guardada')->send();
    }

    public function loadPanel(string $panelId): void
    {
        abort_unless(array_key_exists($panelId, $this->panelOptions()), 403);

        $panel = Filament::getPanel($panelId);
        $plugin = $panel->getPlugin(FilamentPwaPlugin::ID);
        abort_unless($plugin instanceof FilamentPwaPlugin, 404);
        $setting = app(PwaSettingsRepository::class)->find($panelId);

        $this->getFormSchema()->fill([
            'panel_id' => $panelId,
            'name' => $plugin->getName($panel),
            'short_name' => $plugin->getShortName($panel),
            'description' => $plugin->getDescription($panel),
            'theme_color' => $plugin->getThemeColor($panel),
            'background_color' => $plugin->getBackgroundColor($panel),
            'display' => $plugin->getDisplay($panel),
            'orientation' => $plugin->getOrientation($panel),
            'offline_title' => $plugin->getOfflineTitle($panel),
            'offline_message' => $plugin->getOfflineMessage($panel),
            'offline_retry_label' => $plugin->getOfflineRetryLabel($panel),
            'cache_version' => $plugin->getCacheVersion($panel),
            'icon_path' => $setting?->icon_path,
        ]);
    }

    /** @return array<string, string> */
    protected function panelOptions(): array
    {
        $currentPanel = Filament::getCurrentPanel();
        if (! $currentPanel) {
            return [];
        }

        $plugin = $currentPanel->getPlugin(FilamentPwaPlugin::ID);
        if (! $plugin instanceof FilamentPwaPlugin) {
            return [];
        }

        return collect($plugin->getManageablePanelIds($currentPanel))
            ->mapWithKeys(function (string $panelId): array {
                $panel = Filament::getPanel($panelId);

                return $panel->hasPlugin(FilamentPwaPlugin::ID)
                    ? [$panelId => strip_tags((string) $panel->getBrandName())]
                    : [];
            })
            ->all();
    }

    protected function getFormSchema(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('The PWA settings form is not initialized.');
    }
}
