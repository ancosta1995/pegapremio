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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Valor do saque
            $table->string('pix_key_type', 20); // CPF, EMAIL, PHONE, RANDOM
            $table->string('pix_key', 255); // Chave PIX
            $table->string('status', 20)->default('pending'); // pending, processing, approved, rejected, canceled
            $table->text('rejection_reason')->nullable(); // Motivo da rejeição
            $table->decimal('balance_before', 10, 2); // Saldo antes do saque
            $table->decimal('balance_after', 10, 2); // Saldo depois do saque
            $table->decimal('total_deposited_at_time', 10, 2); // Total depositado no momento do saque
            $table->decimal('total_wagered_at_time', 10, 2); // Total apostado no momento do saque
            $table->decimal('rollover_requirement_at_time', 10, 2); // Rollover necessário no momento
            $table->decimal('rollover_progress_at_time', 10, 2); // Progresso do rollover no momento
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};

