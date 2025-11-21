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
        Schema::create('tracking_configs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20); // 'kwai' ou 'facebook'
            $table->string('pixel_id');
            $table->text('access_token');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['source', 'pixel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_configs');
    }
};

