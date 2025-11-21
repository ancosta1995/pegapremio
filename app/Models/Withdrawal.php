<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'pix_key_type',
        'pix_key',
        'status',
        'rejection_reason',
        'balance_before',
        'balance_after',
        'total_deposited_at_time',
        'total_wagered_at_time',
        'rollover_requirement_at_time',
        'rollover_progress_at_time',
        'fee_paid',
        'fee_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'total_deposited_at_time' => 'decimal:2',
            'total_wagered_at_time' => 'decimal:2',
            'rollover_requirement_at_time' => 'decimal:2',
            'rollover_progress_at_time' => 'decimal:2',
        ];
    }

    /**
     * Relacionamento: usuário do saque
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: saques pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: saques aprovados
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}

