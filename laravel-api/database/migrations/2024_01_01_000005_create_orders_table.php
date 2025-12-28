<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('product_id');
            $table->string('product_title');
            $table->integer('uc_amount');
            $table->decimal('amount', 10, 2);
            $table->string('player_id');
            $table->string('player_name');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Customer snapshot
            $table->json('customer')->nullable();
            
            // Delivery info
            $table->json('delivery')->nullable(); // {status: pending|delivered|hold|cancelled, items: [], message: ''}
            
            // Risk assessment
            $table->json('risk')->nullable(); // {score: 0-100, status: CLEAR|FLAGGED, reasons: []}
            
            // Meta info
            $table->json('meta')->nullable(); // {ip, userAgent}
            
            // Payment info
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};