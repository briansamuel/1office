<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Task Templates table - Mẫu công việc
     * Mục đích: Lưu trữ các mẫu task để tái sử dụng
     */
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name'); // Tên template
            $table->text('description')->nullable(); // Mô tả template
            $table->string('category')->nullable(); // Danh mục template
            
            // Template content (JSON structure similar to task)
            $table->json('template_data'); // Dữ liệu template
            // Bao gồm: title_template, description_template, priority, estimated_hours, 
            // custom_fields, checklist, etc.
            
            // Template configuration
            $table->enum('template_type', ['task', 'project', 'workflow'])
                  ->default('task');
            $table->enum('scope', ['personal', 'team', 'department', 'organization', 'public'])
                  ->default('personal');
            
            // Usage and popularity
            $table->integer('usage_count')->default(0); // Số lần sử dụng
            $table->decimal('average_rating', 3, 2)->nullable(); // Đánh giá trung bình
            $table->integer('rating_count')->default(0); // Số lượt đánh giá
            $table->datetime('last_used_at')->nullable(); // Lần sử dụng cuối
            
            // Template versioning
            $table->string('version', 20)->default('1.0'); // Phiên bản template
            $table->foreignId('parent_template_id')->nullable()->constrained('task_templates')->onDelete('set null');
            $table->boolean('is_latest_version')->default(true);
            $table->text('version_notes')->nullable(); // Ghi chú phiên bản
            
            // Access control
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Template nổi bật
            $table->boolean('requires_approval')->default(false); // Cần phê duyệt khi sử dụng
            $table->json('allowed_roles')->nullable(); // Roles được phép sử dụng
            $table->json('allowed_departments')->nullable(); // Phòng ban được phép sử dụng
            
            // Template metadata
            $table->json('tags')->nullable(); // Tags để tìm kiếm
            $table->json('required_fields')->nullable(); // Trường bắt buộc khi tạo từ template
            $table->json('default_assignees')->nullable(); // Người được giao mặc định
            $table->json('checklist_items')->nullable(); // Checklist mặc định
            
            // Approval workflow
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])
                  ->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Ownership and sharing
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Template analytics
            $table->json('usage_analytics')->nullable(); // Phân tích sử dụng
            $table->json('performance_metrics')->nullable(); // Metrics hiệu suất
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['organization_id', 'is_active']);
            $table->index(['department_id', 'scope']);
            $table->index(['template_type', 'category']);
            $table->index(['created_by', 'created_at']);
            $table->index(['is_featured', 'is_active']);
            $table->index(['approval_status', 'approved_at']);
            $table->index(['usage_count', 'average_rating']);
            $table->index(['parent_template_id', 'is_latest_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
