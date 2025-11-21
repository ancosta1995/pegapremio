<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adiciona configurações do Seedpay (usando updateOrCreate para evitar duplicatas)
        $settings = [
            [
                'key' => 'seedpay_public_key',
                'value' => '',
                'type' => 'string',
            ],
            [
                'key' => 'seedpay_secret_key',
                'value' => '',
                'type' => 'string',
            ],
            [
                'key' => 'seedpay_base_url',
                'value' => 'https://api.paymaker.com.br',
                'type' => 'string',
            ],
            [
                'key' => 'seedpay_webhook_secret',
                'value' => '',
                'type' => 'string',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'seedpay_public_key',
                'seedpay_secret_key',
                'seedpay_base_url',
                'seedpay_webhook_secret',
            ])
            ->delete();
    }
};

