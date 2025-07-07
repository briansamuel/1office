<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task File Attachments table - Đính kèm tệp
     * Mục đích: Quản lý các file đính kèm trong task
     */
    public function up(): void
    {
        Schema::create('task_file_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            
            // File information
            $table->string('original_name'); // Tên file gốc
            $table->string('file_name'); // Tên file lưu trữ
            $table->string('file_path'); // Đường dẫn file
            $table->string('file_extension', 10); // Phần mở rộng
            $table->string('mime_type'); // Loại MIME
            $table->bigInteger('file_size'); // Kích thước file (bytes)
            $table->string('file_hash')->nullable(); // Hash để kiểm tra trùng lặp
            
            // File categorization
            $table->enum('file_type', ['document', 'image', 'video', 'audio', 'archive', 'other'])
                  ->default('other');
            $table->enum('attachment_type', ['general', 'requirement', 'deliverable', 'reference', 'completion_proof'])
                  ->default('general');
            // general: file thông thường
            // requirement: file yêu cầu
            // deliverable: sản phẩm bàn giao
            // reference: tài liệu tham khảo
            // completion_proof: bằng chứng hoàn thành
            
            // File metadata
            $table->text('description')->nullable(); // Mô tả file
            $table->json('file_metadata')->nullable(); // Metadata của file (dimensions, duration, etc.)
            $table->integer('version')->default(1); // Phiên bản file
            $table->foreignId('previous_version_id')->nullable()->constrained('task_file_attachments')->onDelete('set null');
            
            // Access control
            $table->enum('visibility', ['public', 'team', 'assignees_only', 'private'])
                  ->default('team');
            $table->boolean('is_downloadable')->default(true);
            $table->boolean('requires_approval')->default(false);
            
            // Status and approval
            $table->enum('status', ['uploaded', 'processing', 'approved', 'rejected', 'archived'])
                  ->default('uploaded');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            
            // Download tracking
            $table->integer('download_count')->default(0);
            $table->datetime('last_downloaded_at')->nullable();
            $table->foreignId('last_downloaded_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Storage information
            $table->string('storage_disk')->default('local'); // local, s3, etc.
            $table->string('storage_path')->nullable(); // Path trong storage
            $table->boolean('is_encrypted')->default(false);
            $table->datetime('expires_at')->nullable(); // Thời gian hết hạn
            
            // Virus scanning
            $table->enum('scan_status', ['pending', 'clean', 'infected', 'error'])
                  ->default('pending');
            $table->datetime('scanned_at')->nullable();
            $table->text('scan_result')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['task_id', 'attachment_type']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['file_type', 'mime_type']);
            $table->index(['status', 'visibility']);
            $table->index(['file_hash']); // For duplicate detection
            $table->index(['expires_at']);
            $table->index(['scan_status', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_file_attachments');
    }
};
