<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Projects table - Quản lý dự án
     * Mục đích: Lưu trữ thông tin các dự án, có thể chứa nhiều tasks
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique(); // Mã dự án định danh
            $table->string('name'); // Tên dự án
            $table->text('description')->nullable(); // Mô tả dự án
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])
                  ->default('planning'); // Trạng thái dự án
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                  ->default('normal'); // Mức ưu tiên
            $table->date('start_date')->nullable(); // Ngày bắt đầu
            $table->date('end_date')->nullable(); // Ngày kết thúc
            $table->date('actual_start_date')->nullable(); // Ngày bắt đầu thực tế
            $table->date('actual_end_date')->nullable(); // Ngày kết thúc thực tế
            $table->decimal('budget', 15, 2)->nullable(); // Ngân sách
            $table->decimal('actual_cost', 15, 2)->default(0); // Chi phí thực tế
            $table->decimal('progress', 5, 2)->default(0); // Tiến độ % (0-100)
            $table->enum('progress_calculation', ['manual', 'auto_by_tasks', 'auto_by_hours'])
                  ->default('auto_by_tasks'); // Cách tính tiến độ
            
            // Relationships
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('parent_project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Metadata
            $table->json('custom_fields')->nullable(); // Trường tùy biến
            $table->json('settings')->nullable(); // Cài đặt dự án
            $table->json('metadata')->nullable(); // Metadata bổ sung
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['organization_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['project_manager_id']);
            $table->index(['parent_project_id']);
            $table->index(['status', 'priority']);
            $table->index(['start_date', 'end_date']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
