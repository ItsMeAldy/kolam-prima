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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->default('Kolam Prima');
            $table->string('tagline')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('hero_headline')->nullable();
            $table->text('hero_subheadline')->nullable();
            $table->string('stat_1_label')->nullable();
            $table->string('stat_1_value')->nullable();
            $table->string('stat_2_label')->nullable();
            $table->string('stat_2_value')->nullable();
            $table->string('stat_3_label')->nullable();
            $table->string('stat_3_value')->nullable();
            $table->string('stat_4_label')->nullable();
            $table->string('stat_4_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
