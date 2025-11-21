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
        Schema::table('withdrawals', function (Blueprint $table) {
            // Taxa de prioridade (segunda taxa)
            $table->boolean('priority_fee_paid')->default(false)->after('fee_paid');
            $table->foreignId('priority_fee_transaction_id')->nullable()->after('fee_transaction_id')->constrained('payment_transactions')->onDelete('set null');
            
            // Posição na fila
            $table->integer('queue_position')->nullable()->after('priority_fee_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropForeign(['priority_fee_transaction_id']);
            $table->dropColumn(['priority_fee_paid', 'priority_fee_transaction_id', 'queue_position']);
        });
    }
};

