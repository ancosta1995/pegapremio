<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'document',
        'referral_code',
        'referred_by',
        'balance',
        'balance_bonus',
        'balance_ref',
        'cpa',
        'cpa_paid',
        'total_deposited',
        'total_wagered',
        'is_demo',
        'is_admin',
        // Tracking fields
        'click_id',
        'pixel_id',
        'kwai_click_id',
        'campaign_id',
        'adset_id',
        'creative_id',
        'utm_source',
        'utm_campaign',
        'utm_medium',
        'utm_content',
        'utm_term',
        'utm_id',
        'fbclid',
        'kwai_content_view_sent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'balance_bonus' => 'decimal:2',
            'balance_ref' => 'decimal:2',
            'cpa' => 'decimal:2',
            'cpa_paid' => 'boolean',
            'total_deposited' => 'decimal:2',
            'total_wagered' => 'decimal:2',
            'is_demo' => 'boolean',
            'is_admin' => 'boolean',
            'kwai_content_view_sent' => 'boolean',
        ];
    }

    /**
     * Boot do modelo - gera código de referência automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateUniqueReferralCode();
            }
        });
    }

    /**
     * Gera um código de referência único
     */
    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(\Illuminate\Support\Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Relacionamento: usuário que indicou este usuário
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by', 'referral_code');
    }

    /**
     * Relacionamento: usuários indicados por este usuário
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by', 'referral_code');
    }

    /**
     * Relacionamento: histórico de jogadas
     */
    public function gameHistory()
    {
        return $this->hasMany(GameHistory::class);
    }

    /**
     * Relacionamento: transações de pagamento
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Relacionamento: saques
     */
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Calcula o progresso do rollover
     * Rollover = total_wagered / (total_deposited * rollover_requirement)
     */
    public function getRolloverProgress(): float
    {
        $rolloverRequirement = \App\Models\SystemSetting::get('rollover_requirement', 1.0);
        $requiredWager = $this->total_deposited * $rolloverRequirement;
        
        if ($requiredWager <= 0) {
            return 1.0; // Se não há depósitos, considera completo
        }
        
        return min(1.0, $this->total_wagered / $requiredWager);
    }

    /**
     * Verifica se o usuário completou o rollover
     */
    public function hasCompletedRollover(): bool
    {
        return $this->getRolloverProgress() >= 1.0;
    }

    /**
     * Verifica se o usuário pode acessar o painel admin do Filament
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_admin ?? false;
    }
}
