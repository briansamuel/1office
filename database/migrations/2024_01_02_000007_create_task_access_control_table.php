<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task Access Control table - Phân quyền task
     * Mục đích: Quản lý quyền truy cập chi tiết cho từng task
     */
    public function up(): void
    {
        Schema::create('task_access_control', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Access permissions
            $table->boolean('can_view')->default(true); // Xem task
            $table->boolean('can_edit')->default(false); // Sửa task
            $table->boolean('can_comment')->default(true); // Bình luận
            $table->boolean('can_upload')->default(false); // Upload file
            $table->boolean('can_download')->default(true); // Download file
            $table->boolean('can_assign')->default(false); // Giao việc cho người khác
            $table->boolean('can_change_status')->default(false); // Thay đổi trạng thái
            $table->boolean('can_log_time')->default(false); // Ghi nhận thời gian
            $table->boolean('can_view_time_logs')->default(false); // Xem time logs
            $table->boolean('can_delete')->default(false); // Xóa task
            
            // Advanced permissions
            $table->boolean('can_view_private_comments')->default(false); // Xem comment nội bộ
            $table->boolean('can_approve_time_logs')->default(false); // Phê duyệt time logs
            $table->boolean('can_manage_subtasks')->default(false); // Quản lý subtask
            $table->boolean('can_view_financial_data')->default(false); // Xem dữ liệu tài chính
            
            // Permission source and metadata
            $table->enum('permission_source', ['direct', 'role', 'project', 'department', 'organization'])
                  ->default('direct');
            // direct: cấp trực tiếp
            // role: từ role của user
            // project: từ quyền trong project
            // department: từ phòng ban
            // organization: từ tổ chức
            
            $table->foreignId('granted_by')->constrained('users')->onDelete('cascade');
            $table->datetime('granted_at')->useCurrent();
            $table->datetime('expires_at')->nullable(); // Thời gian hết hạn quyền
            $table->text('reason')->nullable(); // Lý do cấp quyền
            
            // Status
            $table->enum('status', ['active', 'suspended', 'expired', 'revoked'])
                  ->default('active');
            $table->datetime('last_accessed_at')->nullable(); // Lần truy cập cuối
            
            // Conditions and restrictions
            $table->json('conditions')->nullable(); // Điều kiện áp dụng quyền
            $table->json('restrictions')->nullable(); // Hạn chế cụ thể
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint
            $table->unique(['task_id', 'user_id']);
            
            // Indexes
            $table->index(['task_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['granted_by', 'granted_at']);
            $table->index(['permission_source', 'status']);
            $table->index(['expires_at']);
            $table->index(['last_accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_access_control');
    }
};
