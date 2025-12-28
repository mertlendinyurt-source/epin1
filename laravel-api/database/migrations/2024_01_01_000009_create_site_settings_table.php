<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('site_name')->default('PINLY');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('category_icon')->nullable();
            $table->boolean('daily_banner_enabled')->default(true);
            $table->string('daily_banner_title')->default('Bugüne Özel Fiyatlar');
            $table->string('daily_banner_subtitle')->nullable();
            $table->string('daily_banner_icon')->default('fire');
            $table->boolean('daily_countdown_enabled')->default(true);
            $table->string('daily_countdown_label')->default('Kampanya bitimine');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};