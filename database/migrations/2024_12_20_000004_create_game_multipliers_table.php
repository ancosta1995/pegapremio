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
        Schema::create('game_multipliers', function (Blueprint $table) {
            $table->id();
            $table->decimal('multiplier', 10, 2); // 1.00, 2.00, 5.00, etc
            $table->decimal('probability', 5, 2); // Probabilidade em porcentagem (0.00 a 100.00)
            $table->boolean('is_demo')->default(false); // true para demo, false para real
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0); // Ordem de exibição
            $table->timestamps();
        });

        // Inserir multiplicadores padrão para usuários REAIS (is_demo = false)
        $realMultipliers = [
            ['multiplier' => 1.00, 'probability' => 40.00, 'order' => 1],
            ['multiplier' => 2.00, 'probability' => 25.00, 'order' => 2],
            ['multiplier' => 5.00, 'probability' => 15.00, 'order' => 3],
            ['multiplier' => 10.00, 'probability' => 10.00, 'order' => 4],
            ['multiplier' => 50.00, 'probability' => 7.00, 'order' => 5],
            ['multiplier' => 100.00, 'probability' => 3.00, 'order' => 6],
        ];

        foreach ($realMultipliers as $mult) {
            \DB::table('game_multipliers')->insert([
                'multiplier' => $mult['multiplier'],
                'probability' => $mult['probability'],
                'is_demo' => false,
                'order' => $mult['order'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Inserir multiplicadores padrão para usuários DEMO (is_demo = true)
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_multipliers');
    }
};

