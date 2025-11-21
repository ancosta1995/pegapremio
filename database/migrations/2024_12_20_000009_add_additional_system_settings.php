<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $settings = [
            [
                'key' => 'min_deposit_amount',
                'value' => '20.00',
                'type' => 'decimal',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'rollover_requirement',
                'value' => '1',
                'type' => 'integer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($settings as $setting) {
            if (!\DB::table('system_settings')->where('key', $setting['key'])->exists()) {
                \DB::table('system_settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::table('system_settings')
            ->whereIn('key', ['min_deposit_amount', 'rollover_requirement'])
            ->delete();
    }
};

