<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task Dependencies table - Phụ thuộc công việc
     * Mục đích: Quản lý mối quan hệ phụ thuộc giữa các task
     */
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('predecessor_task_id')->constrained('tasks')->onDelete('cascade'); // Task trước
            $table->foreignId('successor_task_id')->constrained('tasks')->onDelete('cascade'); // Task sau
            
            // Dependency type
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])
                  ->default('finish_to_start');
            // finish_to_start: task trước phải hoàn thành trước khi task sau bắt đầu
            // start_to_start: task sau chỉ có thể bắt đầu sau khi task trước bắt đầu
            // finish_to_finish: task sau chỉ có thể hoàn thành sau khi task trước hoàn thành
            // start_to_finish: task sau chỉ có thể hoàn thành sau khi task trước bắt đầu
            
            // Lag time (delay between tasks)
            $table->integer('lag_days')->default(0); // Số ngày trễ
            $table->integer('lag_hours')->default(0); // Số giờ trễ
            $table->integer('lag_minutes')->default(0); // Số phút trễ
            
            // Dependency strength
            $table->enum('dependency_strength', ['mandatory', 'preferred', 'optional'])
                  ->default('mandatory');
            // mandatory: bắt buộc
            // preferred: ưu tiên
            // optional: tùy chọn
            
            // Status and validation
            $table->enum('status', ['active', 'inactive', 'violated', 'resolved'])
                  ->default('active');
            $table->boolean('is_violated')->default(false); // Có vi phạm dependency không
            $table->datetime('violation_detected_at')->nullable();
            $table->text('violation_reason')->nullable();
            
            // Impact analysis
            $table->enum('impact_level', ['low', 'medium', 'high', 'critical'])
                  ->default('medium');
            $table->text('impact_description')->nullable();
            $table->json('affected_tasks')->nullable(); // Các task bị ảnh hưởng
            
            // Automatic handling
            $table->boolean('auto_adjust_dates')->default(false); // Tự động điều chỉnh ngày
            $table->boolean('send_notifications')->default(true); // Gửi thông báo
            $table->json('notification_recipients')->nullable(); // Người nhận thông báo
            
            // Resolution tracking
            $table->text('resolution_notes')->nullable(); // Ghi chú giải quyết
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('resolved_at')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('dependency_reason')->nullable(); // Lý do tạo dependency
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint - prevent duplicate dependencies
            $table->unique(['predecessor_task_id', 'successor_task_id']);
            
            // Indexes
            $table->index(['predecessor_task_id', 'status']);
            $table->index(['successor_task_id', 'status']);
            $table->index(['dependency_type', 'dependency_strength']);
            $table->index(['is_violated', 'violation_detected_at']);
            $table->index(['impact_level', 'status']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
