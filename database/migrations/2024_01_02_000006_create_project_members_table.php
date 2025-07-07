<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Project Members table - Thành viên dự án
     * Mục đích: Quản lý thành viên tham gia dự án với các vai trò khác nhau
     */
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Role in project
            $table->enum('role', ['manager', 'lead', 'member', 'observer', 'stakeholder', 'client'])
                  ->default('member');
            // manager: quản lý dự án
            // lead: trưởng nhóm
            // member: thành viên
            // observer: người quan sát
            // stakeholder: bên liên quan
            // client: khách hàng
            
            // Permissions in project
            $table->json('permissions')->nullable(); // Quyền cụ thể trong dự án
            $table->boolean('can_create_tasks')->default(false);
            $table->boolean('can_edit_tasks')->default(false);
            $table->boolean('can_delete_tasks')->default(false);
            $table->boolean('can_assign_tasks')->default(false);
            $table->boolean('can_view_reports')->default(false);
            $table->boolean('can_manage_members')->default(false);
            $table->boolean('can_edit_project')->default(false);
            
            // Membership details
            $table->datetime('joined_at')->useCurrent();
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->datetime('left_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'removed'])
                  ->default('active');
            
            // Work allocation
            $table->decimal('allocation_percentage', 5, 2)->default(100); // % thời gian dành cho dự án
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Giá theo giờ
            $table->string('currency', 3)->default('USD');
            
            // Notification preferences
            $table->json('notification_settings')->nullable(); // Cài đặt thông báo
            $table->boolean('receive_task_notifications')->default(true);
            $table->boolean('receive_comment_notifications')->default(true);
            $table->boolean('receive_deadline_notifications')->default(true);
            
            // Performance tracking
            $table->integer('tasks_assigned')->default(0); // Số task được giao
            $table->integer('tasks_completed')->default(0); // Số task hoàn thành
            $table->decimal('total_hours_logged', 10, 2)->default(0); // Tổng giờ làm việc
            $table->decimal('average_task_rating', 3, 2)->nullable(); // Đánh giá trung bình
            
            // Metadata
            $table->text('notes')->nullable(); // Ghi chú về thành viên
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint
            $table->unique(['project_id', 'user_id']);
            
            // Indexes
            $table->index(['project_id', 'role']);
            $table->index(['user_id', 'status']);
            $table->index(['added_by', 'joined_at']);
            $table->index(['status', 'role']);
            $table->index(['joined_at', 'left_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
