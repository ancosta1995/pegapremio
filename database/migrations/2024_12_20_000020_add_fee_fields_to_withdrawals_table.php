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
            $table->boolean('fee_paid')->default(false)->after('status');
            $table->unsignedBigInteger('fee_transaction_id')->nullable()->after('fee_paid');
            $table->foreign('fee_transaction_id')->references('id')->on('payment_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropForeign(['fee_transaction_id']);
            $table->dropColumn(['fee_paid', 'fee_transaction_id']);
        });
    }
};

