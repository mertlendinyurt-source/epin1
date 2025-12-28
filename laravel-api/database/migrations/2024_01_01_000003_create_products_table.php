<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->integer('uc_amount');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('image_url')->nullable();
            $table->string('region_code')->default('TR');
            $table->timestamps();
            
            $table->index('active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};