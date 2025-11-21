<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document')->nullable()->after('phone');
            $table->string('referral_code', 10)->nullable()->after('document');
            $table->string('referred_by', 10)->nullable()->after('referral_code');
            $table->decimal('balance_ref', 10, 2)->default(0)->after('balance_bonus');
            $table->decimal('cpa', 10, 2)->default(0)->after('balance_ref');
            $table->boolean('cpa_paid')->default(false)->after('cpa');
        });

        // Gerar códigos de referência para usuários existentes ANTES de adicionar o índice único
        $users = \DB::table('users')->whereNull('referral_code')->get();
        foreach ($users as $user) {
            $code = $this->generateUniqueReferralCode();
            \DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
        }

        // Adicionar índice único após gerar todos os códigos
        Schema::table('users', function (Blueprint $table) {
            $table->unique('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['document', 'referral_code', 'referred_by', 'balance_ref', 'cpa', 'cpa_paid']);
        });
    }

    /**
     * Gera um código de referência único
     */
    private function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (\DB::table('users')->where('referral_code', $code)->exists());

        return $code;
    }
};

