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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Session information
            $table->string('session_token')->unique();
            $table->string('device_name')->nullable();
            $table->enum('device_type', ['mobile', 'tablet', 'desktop', 'unknown'])->default('unknown');
            $table->string('device_id')->nullable(); // Unique device identifier
            
            // Network information
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('location')->nullable(); // City, Country
            
            // Session status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity')->nullable();
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('expires_at');
            
            // Additional metadata
            $table->json('metadata')->nullable(); // Store additional device/session info
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['session_token']);
            $table->index(['ip_address']);
            $table->index(['device_type']);
            $table->index(['last_activity']);
            $table->index(['expires_at']);
            
            // Composite indexes
            $table->index(['user_id', 'device_type', 'is_active']);
            $table->index(['user_id', 'last_activity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
