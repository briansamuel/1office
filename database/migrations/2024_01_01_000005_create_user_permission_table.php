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
        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_granted')->default(true); // true = grant, false = deny
            $table->json('conditions')->nullable(); // Override permission conditions
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // Permission expiration
            $table->text('reason')->nullable(); // Reason for granting/denying
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
            $table->index(['user_id', 'is_granted']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permission');
    }
};
