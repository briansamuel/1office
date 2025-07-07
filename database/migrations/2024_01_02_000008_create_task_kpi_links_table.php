<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task KPI Links table - Liên kết KPI
     * Mục đích: Liên kết task với các chỉ số KPI để đo lường hiệu suất
     */
    public function up(): void
    {
        Schema::create('task_kpi_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            
            // KPI reference (flexible design for future KPI module)
            $table->string('kpi_type'); // Type of KPI (performance, quality, time, cost, etc.)
            $table->string('kpi_identifier'); // KPI ID or code
            $table->string('kpi_name'); // KPI name for reference
            $table->text('kpi_description')->nullable(); // KPI description
            
            // Link configuration
            $table->enum('link_type', ['contributes_to', 'measures', 'impacts', 'depends_on'])
                  ->default('contributes_to');
            // contributes_to: task đóng góp vào KPI
            // measures: task đo lường KPI
            // impacts: task ảnh hưởng đến KPI
            // depends_on: task phụ thuộc vào KPI
            
            $table->decimal('weight_percentage', 5, 2)->default(100); // Trọng số % trong KPI
            $table->decimal('target_value', 15, 4)->nullable(); // Giá trị mục tiêu
            $table->decimal('actual_value', 15, 4)->nullable(); // Giá trị thực tế
            $table->string('unit_of_measure')->nullable(); // Đơn vị đo
            
            // Calculation method
            $table->enum('calculation_method', ['completion_based', 'time_based', 'quality_based', 'custom'])
                  ->default('completion_based');
            $table->json('calculation_config')->nullable(); // Cấu hình tính toán
            
            // Status and tracking
            $table->enum('status', ['active', 'inactive', 'completed', 'cancelled'])
                  ->default('active');
            $table->boolean('auto_update')->default(true); // Tự động cập nhật từ task
            $table->datetime('last_calculated_at')->nullable();
            $table->decimal('achievement_percentage', 5, 2)->default(0); // % đạt được
            
            // Thresholds and alerts
            $table->decimal('warning_threshold', 5, 2)->nullable(); // Ngưỡng cảnh báo
            $table->decimal('critical_threshold', 5, 2)->nullable(); // Ngưỡng nghiêm trọng
            $table->boolean('send_alerts')->default(false);
            $table->json('alert_recipients')->nullable(); // Người nhận cảnh báo
            
            // Reporting and analysis
            $table->enum('reporting_frequency', ['real_time', 'daily', 'weekly', 'monthly', 'quarterly'])
                  ->default('real_time');
            $table->json('historical_data')->nullable(); // Dữ liệu lịch sử
            $table->text('analysis_notes')->nullable(); // Ghi chú phân tích
            
            // Link metadata
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('link_reason')->nullable(); // Lý do liên kết
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['task_id', 'status']);
            $table->index(['kpi_type', 'kpi_identifier']);
            $table->index(['link_type', 'status']);
            $table->index(['auto_update', 'last_calculated_at']);
            $table->index(['achievement_percentage']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_kpi_links');
    }
};
