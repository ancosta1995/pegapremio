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
        Schema::table('users', function (Blueprint $table) {
            $table->string('click_id')->nullable()->after('document');
            $table->string('pixel_id')->nullable()->after('click_id');
            $table->string('campaign_id')->nullable()->after('pixel_id');
            $table->string('adset_id')->nullable()->after('campaign_id');
            $table->string('creative_id')->nullable()->after('adset_id');
            $table->string('utm_source')->nullable()->after('creative_id');
            $table->string('utm_campaign')->nullable()->after('utm_source');
            $table->string('utm_medium')->nullable()->after('utm_campaign');
            $table->string('utm_content')->nullable()->after('utm_medium');
            $table->string('utm_term')->nullable()->after('utm_content');
            $table->string('utm_id')->nullable()->after('utm_term');
            $table->string('fbclid')->nullable()->after('utm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'click_id',
                'pixel_id',
                'campaign_id',
                'adset_id',
                'creative_id',
                'utm_source',
                'utm_campaign',
                'utm_medium',
                'utm_content',
                'utm_term',
                'utm_id',
                'fbclid',
            ]);
        });
    }
};

