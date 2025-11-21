<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'pixel_id',
        'access_token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Busca configuração ativa por source e pixel_id
     */
    public static function getActiveConfig(string $source, string $pixelId): ?self
    {
        return static::where('source', $source)
            ->where('pixel_id', $pixelId)
            ->where('is_active', true)
            ->first();
    }
}

