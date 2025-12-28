<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ga4_measurement_id')->nullable();
            $table->string('gsc_verification_code')->nullable();
            $table->boolean('enable_analytics')->default(false);
            $table->boolean('enable_search_console')->default(false);
            $table->boolean('active')->default(true);
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};