<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_transaction_id',
        'amount',
        'payment_method',
        'transaction_type',
        'status',
        'payment_url',
        'qr_code',
        'qr_code_text',
        'error_message',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
        ];
    }

    /**
     * Relacionamento: usuário da transação
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: transações pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: transações aprovadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: transações por gateway
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}

