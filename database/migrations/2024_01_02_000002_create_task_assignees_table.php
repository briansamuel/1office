<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task Assignees table - Giao việc và thực hiện
     * Mục đích: Quản lý nhiều người tham gia vào một task với các vai trò khác nhau
     */
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Role in task
            $table->enum('role', ['executor', 'assigner', 'follower', 'reviewer', 'approver'])
                  ->default('executor');
            // executor: người thực hiện
            // assigner: người giao việc
            // follower: người theo dõi
            // reviewer: người review
            // approver: người phê duyệt
            
            // Assignment details
            $table->datetime('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->text('assignment_note')->nullable(); // Ghi chú khi giao việc
            
            // Status and progress
            $table->enum('status', ['pending', 'accepted', 'rejected', 'in_progress', 'completed'])
                  ->default('pending');
            $table->decimal('individual_progress', 5, 2)->default(0); // Tiến độ cá nhân (0-100)
            $table->decimal('effort_percentage', 5, 2)->default(100); // % effort trong task
            
            // Time tracking
            $table->datetime('accepted_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->default(0);
            
            // Completion details
            $table->text('completion_note')->nullable(); // Ghi chú khi hoàn thành
            $table->json('completion_attachments')->nullable(); // File đính kèm khi hoàn thành
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint - một user chỉ có một role trong một task
            $table->unique(['task_id', 'user_id', 'role']);
            
            // Indexes
            $table->index(['task_id', 'role']);
            $table->index(['user_id', 'status']);
            $table->index(['assigned_by', 'assigned_at']);
            $table->index(['status', 'role']);
            $table->index(['assigned_at', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
