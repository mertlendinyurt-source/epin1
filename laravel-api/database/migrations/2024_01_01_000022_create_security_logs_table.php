<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // hash_mismatch, brute_force, etc.
            $table->string('order_id')->nullable();
            $table->string('ip')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};