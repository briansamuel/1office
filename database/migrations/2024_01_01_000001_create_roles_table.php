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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin, manager, employee, etc.
            $table->string('slug')->unique(); // admin, manager, employee, etc.
            $table->string('display_name'); // Administrator, Manager, Employee, etc.
            $table->text('description')->nullable();
            $table->string('module')->nullable(); // work, hrm, crm, warehouse, system
            $table->integer('level')->default(0); // Role hierarchy level (higher = more permissions)
            $table->boolean('is_system_role')->default(false); // System roles cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional role configuration
            $table->timestamps();

            $table->index(['is_active', 'module']);
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
