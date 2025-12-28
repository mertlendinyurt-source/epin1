<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->string('id')->primary()->default('main');
            $table->boolean('enable_email')->default(false);
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('smtp_host')->nullable();
            $table->string('smtp_port')->default('587');
            $table->boolean('smtp_secure')->default(false);
            $table->string('smtp_user')->nullable();
            $table->text('smtp_pass')->nullable(); // Encrypted
            $table->string('test_recipient_email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};