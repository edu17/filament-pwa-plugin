<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Support;

use Edu17\FilamentPwaPlugin\Models\PwaSetting;

class PwaSettingsRepository
{
    /** @var array<string, PwaSetting | null> */
    protected array $settings = [];

    public function find(string $panelId): ?PwaSetting
    {
        if (array_key_exists($panelId, $this->settings)) {
            return $this->settings[$panelId];
        }

        return $this->settings[$panelId] = PwaSetting::query()
            ->where('panel_id', $panelId)
            ->first();
    }

    public function value(string $panelId, string $key): mixed
    {
        return data_get($this->find($panelId)?->data, $key);
    }

    /** @param array<string, mixed> $data */
    public function save(string $panelId, array $data, ?string $iconPath): PwaSetting
    {
        $setting = PwaSetting::query()->updateOrCreate(
            ['panel_id' => $panelId],
            ['data' => $data, 'icon_path' => $iconPath],
        );

        $this->settings[$panelId] = $setting;

        return $setting;
    }

    public function forget(string $panelId): void
    {
        unset($this->settings[$panelId]);
    }
}
