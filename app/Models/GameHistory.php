<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameHistory extends Model
{
    protected $table = 'game_history';

    protected $fillable = [
        'user_id',
        'bet_amount',
        'collision_type',
        'is_win',
        'win_amount',
        'multiplier',
        'balance_before',
        'balance_after',
        'is_demo',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'is_win' => 'boolean',
        'win_amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'is_demo' => 'boolean',
    ];

    /**
     * Relacionamento com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

