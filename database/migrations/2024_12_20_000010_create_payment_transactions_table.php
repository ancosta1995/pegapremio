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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('gateway', 50); // Nome do gateway (Seedpay, etc)
            $table->string('gateway_transaction_id')->nullable(); // ID da transação no gateway
            $table->decimal('amount', 10, 2); // Valor da transação
            $table->string('payment_method', 20)->default('PIX'); // PIX, CREDIT_CARD, BOLETO
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, canceled, refunded
            $table->text('payment_url')->nullable(); // URL para pagamento
            $table->text('qr_code')->nullable(); // QR Code (base64 ou URL)
            $table->text('qr_code_text')->nullable(); // Texto do QR Code (chave PIX)
            $table->text('error_message')->nullable(); // Mensagem de erro se houver
            $table->json('gateway_response')->nullable(); // Resposta completa do gateway
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('user_id');
            $table->index('gateway_transaction_id');
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
        Schema::dropIfExists('payment_transactions');
    }
};

