<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider'); // google
            $table->boolean('enabled')->default(false);
            $table->text('client_id')->nullable(); // Encrypted
            $table->text('client_secret')->nullable(); // Encrypted
            $table->string('updated_by')->nullable();
            $table->timestamps();
            
            $table->unique('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_settings');
    }
};