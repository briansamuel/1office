<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task Comments table - Bình luận công việc
     * Mục đích: Lưu trữ các bình luận, thảo luận về task
     */
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Comment content
            $table->text('content'); // Nội dung bình luận
            $table->enum('type', ['comment', 'status_change', 'assignment', 'progress_update', 'system'])
                  ->default('comment');
            // comment: bình luận thường
            // status_change: thay đổi trạng thái
            // assignment: giao việc
            // progress_update: cập nhật tiến độ
            // system: thông báo hệ thống
            
            // Reply functionality
            $table->foreignId('parent_comment_id')->nullable()->constrained('task_comments')->onDelete('cascade');
            $table->integer('reply_level')->default(0); // Cấp độ reply (0 = comment gốc)
            
            // Attachments and mentions
            $table->json('attachments')->nullable(); // File đính kèm
            $table->json('mentioned_users')->nullable(); // User được mention (@user)
            
            // Status and visibility
            $table->boolean('is_internal')->default(false); // Chỉ nội bộ team
            $table->boolean('is_pinned')->default(false); // Ghim comment
            $table->boolean('is_edited')->default(false); // Đã chỉnh sửa
            $table->datetime('edited_at')->nullable();
            
            // Reactions and interactions
            $table->json('reactions')->nullable(); // Reactions (like, love, etc.)
            $table->integer('replies_count')->default(0); // Số lượng reply
            
            // Metadata
            $table->json('metadata')->nullable(); // Metadata cho system comments
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['parent_comment_id']);
            $table->index(['type', 'created_at']);
            $table->index(['is_internal', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
