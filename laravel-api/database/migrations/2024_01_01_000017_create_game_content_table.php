<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_content', function (Blueprint $table) {
            $table->string('game')->primary();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->decimal('default_rating', 3, 2)->default(5.0);
            $table->integer('default_review_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_content');
    }
};