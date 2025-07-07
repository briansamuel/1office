<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tasks table - Quản lý công việc
     * Mục đích: Lưu trữ thông tin chi tiết các công việc/task
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique(); // Mã công việc định danh
            $table->string('title'); // Tên công việc
            $table->text('description')->nullable(); // Mô tả công việc

            // Status and Priority
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'completed', 'cancelled', 'on_hold'])
                  ->default('todo'); // Trạng thái công việc
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                  ->default('normal'); // Mức ưu tiên

            // Progress tracking
            $table->enum('progress_type', ['manual', 'auto_by_assignee', 'auto_by_subtasks'])
                  ->default('manual'); // Cách tính tiến độ
            $table->decimal('progress', 5, 2)->default(0); // Tiến độ % (0-100)

            // Time management
            $table->datetime('start_time')->nullable(); // Thời gian bắt đầu
            $table->datetime('end_time')->nullable(); // Thời gian kết thúc
            $table->datetime('actual_start_time')->nullable(); // Thời gian bắt đầu thực tế
            $table->datetime('actual_end_time')->nullable(); // Thời gian kết thúc thực tế
            $table->decimal('estimated_hours', 8, 2)->nullable(); // Số giờ ước tính
            $table->decimal('actual_hours', 8, 2)->default(0); // Số giờ thực tế

            // Task hierarchy and relationships
            $table->boolean('is_milestone')->default(false); // Có phải là mốc không
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');

            // Organization and assignment
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            // Completion requirements
            $table->boolean('require_description_on_complete')->default(false);
            $table->boolean('require_attachment_on_complete')->default(false);
            $table->text('completion_description')->nullable(); // Mô tả khi hoàn thành

            // Metadata
            $table->json('tags')->nullable(); // Tags
            $table->json('custom_fields')->nullable(); // Trường tùy biến
            $table->json('metadata')->nullable(); // Metadata bổ sung

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['organization_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['parent_task_id']);
            $table->index(['assigned_to', 'status']);
            $table->index(['created_by', 'created_at']);
            $table->index(['status', 'priority']);
            $table->index(['start_time', 'end_time']);
            $table->index(['is_milestone', 'status']);
            $table->index(['progress_type', 'progress']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
