<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'kwai_pixel_id'],
            [
                'value' => '',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'kwai_access_token'],
            [
                'value' => '',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'kwai_tracking_webhook_url'],
            [
                'value' => '',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'kwai_pixel_id',
            'kwai_access_token',
            'kwai_tracking_webhook_url',
        ])->delete();
    }
};

