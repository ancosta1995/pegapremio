<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMultiplier extends Model
{
    protected $fillable = ['multiplier', 'probability', 'is_demo', 'active', 'order'];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'probability' => 'decimal:2',
        'is_demo' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Seleciona um multiplicador aleatório baseado nas probabilidades
     * @param bool $isDemo Se true, retorna multiplicadores para usuários demo
    */
    public static function getRandomMultiplier($isDemo = false)
    {
        $multipliers = static::where('active', true)
            ->where('is_demo', $isDemo)
            ->orderBy('order')
            ->get();
        
        if ($multipliers->isEmpty()) {
            return 1.00; // Fallback
        }

        // Gera um número aleatório entre 0 e 100
        $random = mt_rand(0, 10000) / 100; // 0.00 a 100.00 com 2 casas decimais
        
        $cumulative = 0;
        foreach ($multipliers as $multiplier) {
            $cumulative += $multiplier->probability;
            if ($random <= $cumulative) {
                return (float) $multiplier->multiplier;
            }
        }
        
        // Se não encontrou (por algum motivo), retorna o primeiro
        return (float) $multipliers->first()->multiplier;
    }
}

