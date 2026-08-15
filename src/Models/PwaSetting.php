<?php

declare(strict_types=1);

namespace Edu17\FilamentPwaPlugin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed> $data
 * @property string|null $icon_path
 */
class PwaSetting extends Model
{
    protected $table = 'filament_pwa_settings';

    /** @var list<string> */
    protected $fillable = [
        'panel_id',
        'data',
        'icon_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
