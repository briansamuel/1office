<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task History table - Lịch sử thay đổi
     * Mục đích: Theo dõi tất cả thay đổi của task để audit và rollback
     */
    public function up(): void
    {
        Schema::create('task_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Người thực hiện thay đổi
            
            // Change information
            $table->enum('action', ['created', 'updated', 'deleted', 'restored', 'status_changed', 
                         'assigned', 'unassigned', 'commented', 'file_uploaded', 'time_logged'])
                  ->default('updated');
            $table->string('field_name')->nullable(); // Tên trường thay đổi
            $table->text('old_value')->nullable(); // Giá trị cũ
            $table->text('new_value')->nullable(); // Giá trị mới
            $table->json('full_old_data')->nullable(); // Toàn bộ dữ liệu cũ (cho rollback)
            $table->json('full_new_data')->nullable(); // Toàn bộ dữ liệu mới
            
            // Change context
            $table->text('change_reason')->nullable(); // Lý do thay đổi
            $table->string('change_source')->nullable(); // Nguồn thay đổi (web, api, mobile, system)
            $table->string('ip_address')->nullable(); // IP address
            $table->string('user_agent')->nullable(); // User agent
            $table->json('request_data')->nullable(); // Dữ liệu request
            
            // Impact analysis
            $table->json('affected_fields')->nullable(); // Các trường bị ảnh hưởng
            $table->json('related_changes')->nullable(); // Thay đổi liên quan
            $table->boolean('is_significant')->default(false); // Thay đổi quan trọng
            $table->enum('impact_level', ['low', 'medium', 'high', 'critical'])
                  ->default('low');
            
            // Rollback information
            $table->boolean('can_rollback')->default(true); // Có thể rollback không
            $table->foreignId('rollback_of')->nullable()->constrained('task_history')->onDelete('set null');
            $table->boolean('is_rollback')->default(false); // Đây có phải là rollback không
            $table->datetime('rollback_deadline')->nullable(); // Hạn rollback
            
            // Approval and verification
            $table->boolean('requires_approval')->default(false); // Cần phê duyệt
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'auto_approved'])
                  ->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // System metadata
            $table->string('system_version')->nullable(); // Phiên bản hệ thống
            $table->string('module_version')->nullable(); // Phiên bản module
            $table->json('system_metadata')->nullable(); // Metadata hệ thống
            
            // Notification tracking
            $table->boolean('notifications_sent')->default(false);
            $table->json('notification_recipients')->nullable();
            $table->datetime('notifications_sent_at')->nullable();
            
            // Data retention
            $table->datetime('expires_at')->nullable(); // Thời gian hết hạn lưu trữ
            $table->boolean('is_archived')->default(false); // Đã archive chưa
            $table->datetime('archived_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'action']);
            $table->index(['action', 'created_at']);
            $table->index(['field_name', 'action']);
            $table->index(['is_significant', 'impact_level']);
            $table->index(['can_rollback', 'rollback_deadline']);
            $table->index(['approval_status', 'requires_approval']);
            $table->index(['is_archived', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_history');
    }
};
