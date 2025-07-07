<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task Time Logs table - Nhật ký thời gian
     * Mục đích: Theo dõi thời gian làm việc của từng người trên task
     */
    public function up(): void
    {
        Schema::create('task_time_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Time tracking
            $table->datetime('start_time'); // Thời gian bắt đầu
            $table->datetime('end_time')->nullable(); // Thời gian kết thúc
            $table->decimal('duration_hours', 8, 2)->default(0); // Thời lượng (giờ)
            $table->decimal('billable_hours', 8, 2)->default(0); // Giờ tính phí
            
            // Log details
            $table->text('description')->nullable(); // Mô tả công việc đã làm
            $table->enum('type', ['manual', 'timer', 'imported', 'system'])
                  ->default('manual');
            // manual: nhập tay
            // timer: dùng timer
            // imported: import từ hệ thống khác
            // system: tự động từ hệ thống
            
            // Status and approval
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                  ->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            
            // Billing and cost
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Giá theo giờ
            $table->decimal('total_cost', 10, 2)->default(0); // Tổng chi phí
            $table->string('currency', 3)->default('USD');
            
            // Location and device tracking
            $table->string('ip_address')->nullable();
            $table->json('location_data')->nullable(); // GPS, địa chỉ
            $table->string('device_info')->nullable();
            
            // Break time tracking
            $table->decimal('break_duration_minutes', 8, 2)->default(0); // Thời gian nghỉ
            $table->json('break_logs')->nullable(); // Chi tiết các lần nghỉ
            
            // Productivity metrics
            $table->integer('productivity_score')->nullable(); // Điểm hiệu suất (1-100)
            $table->json('activity_data')->nullable(); // Dữ liệu hoạt động
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['task_id', 'start_time']);
            $table->index(['user_id', 'start_time']);
            $table->index(['status', 'approved_at']);
            $table->index(['type', 'created_at']);
            $table->index(['start_time', 'end_time']);
            $table->index(['billable_hours', 'total_cost']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_time_logs');
    }
};
