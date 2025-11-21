<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 10, 2); // Valor da aposta
            $table->string('collision_type', 10); // 'bomb', 'prize', 'none'
            $table->boolean('is_win')->default(false);
            $table->decimal('win_amount', 10, 2)->default(0); // Valor ganho
            $table->decimal('multiplier', 10, 2)->default(0); // Multiplicador aplicado
            $table->decimal('balance_before', 10, 2); // Saldo antes da jogada
            $table->decimal('balance_after', 10, 2); // Saldo depois da jogada
            $table->boolean('is_demo')->default(false); // Se era usuário demo
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_history');
    }
};

