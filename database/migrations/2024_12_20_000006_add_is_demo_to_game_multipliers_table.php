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
        // Verifica se a coluna já existe antes de adicionar
        if (!Schema::hasColumn('game_multipliers', 'is_demo')) {
            Schema::table('game_multipliers', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('probability');
            });

            // Atualiza os registros existentes para is_demo = false (usuários reais)
            \DB::table('game_multipliers')->whereNull('is_demo')->update(['is_demo' => false]);

            // Insere multiplicadores para usuários DEMO (is_demo = true)
            // Valores mais generosos para demo (maior RTP)
            $demoMultipliers = [
                ['multiplier' => 1.00, 'probability' => 30.00, 'order' => 1],
                ['multiplier' => 2.00, 'probability' => 25.00, 'order' => 2],
                ['multiplier' => 5.00, 'probability' => 20.00, 'order' => 3],
                ['multiplier' => 10.00, 'probability' => 15.00, 'order' => 4],
                ['multiplier' => 50.00, 'probability' => 7.00, 'order' => 5],
                ['multiplier' => 100.00, 'probability' => 3.00, 'order' => 6],
            ];

            foreach ($demoMultipliers as $mult) {
                \DB::table('game_multipliers')->insert([
                    'multiplier' => $mult['multiplier'],
                    'probability' => $mult['probability'],
                    'is_demo' => true,
                    'order' => $mult['order'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_multipliers', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};

