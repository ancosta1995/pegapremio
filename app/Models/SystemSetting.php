<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    /**
     * Obtém o valor de uma configuração
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        switch ($setting->type) {
            case 'decimal':
                return (float) $setting->value;
            case 'integer':
                return (int) $setting->value;
            case 'boolean':
                return (bool) $setting->value;
            default:
                return $setting->value;
        }
    }

    /**
     * Define o valor de uma configuração
     */
    public static function set($key, $value, $type = 'string')
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}

