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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // create_tasks, edit_users, view_reports, etc.
            $table->string('slug')->unique(); // create-tasks, edit-users, view-reports, etc.
            $table->string('display_name'); // Create Tasks, Edit Users, View Reports, etc.
            $table->text('description')->nullable();
            $table->string('module'); // work, hrm, crm, warehouse, system
            $table->string('resource'); // tasks, users, customers, products, etc.
            $table->string('action'); // create, read, update, delete, manage, etc.
            $table->string('scope')->default('own'); // own, department, company, all
            $table->json('conditions')->nullable(); // Additional conditions for permission
            $table->boolean('is_system_permission')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module', 'resource', 'action']);
            $table->index(['is_active', 'module']);
            $table->index('scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
