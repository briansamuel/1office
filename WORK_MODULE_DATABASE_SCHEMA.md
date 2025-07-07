# Work Module Database Schema Documentation

## Tổng quan

Module Work (Quản lý công việc) được thiết kế để quản lý toàn bộ quy trình làm việc từ dự án đến task cá nhân, bao gồm phân quyền, theo dõi thời gian, và liên kết KPI.

## Kiến trúc Database

### 🏗️ **Cấu trúc chính**

```
Projects (Dự án)
├── Tasks (Công việc)
│   ├── Task Assignees (Người thực hiện)
│   ├── Task Comments (Bình luận)
│   ├── Task Time Logs (Nhật ký thời gian)
│   ├── Task File Attachments (File đính kèm)
│   ├── Task Access Control (Phân quyền)
│   ├── Task KPI Links (Liên kết KPI)
│   ├── Task Dependencies (Phụ thuộc)
│   └── Task History (Lịch sử)
├── Project Members (Thành viên dự án)
└── Task Templates (Mẫu công việc)
```

## Chi tiết các bảng

### 1. **projects** - Quản lý dự án

**Mục đích:** Lưu trữ thông tin các dự án, có thể chứa nhiều tasks

**Các trường chính:**
- `uuid`, `code`: Định danh duy nhất
- `name`, `description`: Thông tin cơ bản
- `status`: planning, active, on_hold, completed, cancelled
- `priority`: low, normal, high, urgent
- `start_date`, `end_date`: Thời gian dự kiến
- `actual_start_date`, `actual_end_date`: Thời gian thực tế
- `budget`, `actual_cost`: Quản lý ngân sách
- `progress`: Tiến độ % (0-100)
- `progress_calculation`: Cách tính tiến độ (manual, auto_by_tasks, auto_by_hours)

**Relationships:**
- Thuộc về `organization` và `department`
- Có thể có `parent_project` (dự án con)
- Có `project_manager` và người tạo/cập nhật
- Chứa nhiều `tasks` và `project_members`

### 2. **tasks** - Quản lý công việc

**Mục đích:** Lưu trữ thông tin chi tiết các công việc/task

**Các trường chính:**
- `uuid`, `code`: Định danh duy nhất
- `title`, `description`: Thông tin cơ bản
- `status`: todo, in_progress, in_review, completed, cancelled, on_hold
- `priority`: low, normal, high, urgent
- `progress_type`: Cách tính tiến độ (manual, auto_by_assignee, auto_by_subtasks)
- `progress`: Tiến độ % (0-100)
- `start_time`, `end_time`: Thời gian dự kiến
- `actual_start_time`, `actual_end_time`: Thời gian thực tế
- `estimated_hours`, `actual_hours`: Ước tính và thực tế thời gian
- `is_milestone`: Đánh dấu mốc quan trọng
- `require_description_on_complete`: Yêu cầu mô tả khi hoàn thành
- `require_attachment_on_complete`: Yêu cầu file khi hoàn thành

**Relationships:**
- Thuộc về `project`, `organization`, `department`
- Có thể có `parent_task` (subtask)
- Có người `assigned_to`, `created_by`, `updated_by`
- Có nhiều `assignees`, `comments`, `time_logs`, `attachments`

### 3. **task_assignees** - Giao việc và thực hiện

**Mục đích:** Quản lý nhiều người tham gia vào một task với các vai trò khác nhau

**Các vai trò (role):**
- `executor`: Người thực hiện
- `assigner`: Người giao việc
- `follower`: Người theo dõi
- `reviewer`: Người review
- `approver`: Người phê duyệt

**Tính năng:**
- Theo dõi tiến độ cá nhân
- Phân bổ effort percentage
- Ghi nhận thời gian estimated/actual
- Trạng thái: pending, accepted, rejected, in_progress, completed

### 4. **task_comments** - Bình luận công việc

**Mục đích:** Lưu trữ các bình luận, thảo luận về task

**Loại comment (type):**
- `comment`: Bình luận thường
- `status_change`: Thay đổi trạng thái
- `assignment`: Giao việc
- `progress_update`: Cập nhật tiến độ
- `system`: Thông báo hệ thống

**Tính năng:**
- Reply comments (nested)
- Mention users (@user)
- File attachments
- Reactions (like, love, etc.)
- Internal comments (chỉ nội bộ team)

### 5. **task_time_logs** - Nhật ký thời gian

**Mục đích:** Theo dõi thời gian làm việc của từng người trên task

**Tính năng:**
- Time tracking với start/end time
- Billable vs non-billable hours
- Approval workflow
- Cost calculation với hourly rate
- Location và device tracking
- Break time tracking
- Productivity scoring

### 6. **task_file_attachments** - Đính kèm tệp

**Mục đích:** Quản lý các file đính kèm trong task

**Loại attachment:**
- `general`: File thông thường
- `requirement`: File yêu cầu
- `deliverable`: Sản phẩm bàn giao
- `reference`: Tài liệu tham khảo
- `completion_proof`: Bằng chứng hoàn thành

**Tính năng:**
- File versioning
- Virus scanning
- Access control (visibility levels)
- Download tracking
- Storage optimization (multiple disks)

### 7. **project_members** - Thành viên dự án

**Mục đích:** Quản lý thành viên tham gia dự án với các vai trò khác nhau

**Vai trò:**
- `manager`: Quản lý dự án
- `lead`: Trưởng nhóm
- `member`: Thành viên
- `observer`: Người quan sát
- `stakeholder`: Bên liên quan
- `client`: Khách hàng

**Permissions:**
- Granular permissions cho từng thành viên
- Notification preferences
- Performance tracking

### 8. **task_access_control** - Phân quyền task

**Mục đích:** Quản lý quyền truy cập chi tiết cho từng task

**Permissions:**
- `can_view`, `can_edit`, `can_comment`
- `can_upload`, `can_download`
- `can_assign`, `can_change_status`
- `can_log_time`, `can_view_time_logs`
- `can_delete`, `can_manage_subtasks`

**Permission sources:**
- `direct`: Cấp trực tiếp
- `role`: Từ role của user
- `project`: Từ quyền trong project
- `department`: Từ phòng ban
- `organization`: Từ tổ chức

### 9. **task_kpi_links** - Liên kết KPI

**Mục đích:** Liên kết task với các chỉ số KPI để đo lường hiệu suất

**Link types:**
- `contributes_to`: Task đóng góp vào KPI
- `measures`: Task đo lường KPI
- `impacts`: Task ảnh hưởng đến KPI
- `depends_on`: Task phụ thuộc vào KPI

**Tính năng:**
- Auto calculation từ task progress
- Threshold alerts
- Historical tracking
- Performance analytics

### 10. **task_dependencies** - Phụ thuộc công việc

**Mục đích:** Quản lý mối quan hệ phụ thuộc giữa các task

**Dependency types:**
- `finish_to_start`: Task trước phải hoàn thành trước khi task sau bắt đầu
- `start_to_start`: Task sau chỉ có thể bắt đầu sau khi task trước bắt đầu
- `finish_to_finish`: Task sau chỉ có thể hoàn thành sau khi task trước hoàn thành
- `start_to_finish`: Task sau chỉ có thể hoàn thành sau khi task trước bắt đầu

**Tính năng:**
- Lag time configuration
- Dependency strength (mandatory, preferred, optional)
- Violation detection
- Impact analysis
- Auto date adjustment

### 11. **task_templates** - Mẫu công việc

**Mục đích:** Lưu trữ các mẫu task để tái sử dụng

**Template types:**
- `task`: Mẫu task đơn lẻ
- `project`: Mẫu dự án
- `workflow`: Mẫu quy trình

**Tính năng:**
- Template versioning
- Usage analytics
- Rating system
- Approval workflow
- Scope control (personal, team, department, organization)

### 12. **task_history** - Lịch sử thay đổi

**Mục đích:** Theo dõi tất cả thay đổi của task để audit và rollback

**Actions tracked:**
- `created`, `updated`, `deleted`, `restored`
- `status_changed`, `assigned`, `unassigned`
- `commented`, `file_uploaded`, `time_logged`

**Tính năng:**
- Full data snapshots cho rollback
- Impact analysis
- Approval workflow cho changes
- Data retention policies

## Indexes và Performance

### **Primary Indexes:**
- Tất cả bảng có UUID và auto-increment ID
- Foreign key indexes tự động
- Composite indexes cho queries thường dùng

### **Performance Indexes:**
```sql
-- Tasks
INDEX(organization_id, status)
INDEX(project_id, status)
INDEX(assigned_to, status)
INDEX(status, priority)
INDEX(start_time, end_time)

-- Time Logs
INDEX(task_id, start_time)
INDEX(user_id, start_time)
INDEX(billable_hours, total_cost)

-- Comments
INDEX(task_id, created_at)
INDEX(type, created_at)

-- File Attachments
INDEX(task_id, attachment_type)
INDEX(file_hash) -- For duplicate detection
```

## Soft Deletes

Tất cả bảng chính đều implement soft deletes:
- `deleted_at` timestamp
- Preserve data integrity
- Allow data recovery
- Audit trail maintenance

## JSON Fields

Sử dụng JSON fields cho:
- `custom_fields`: Trường tùy biến
- `metadata`: Dữ liệu bổ sung
- `settings`: Cài đặt
- `tags`: Tags array
- `permissions`: Permission arrays
- `analytics`: Analytics data

## Security Considerations

1. **Row Level Security:** Mỗi record thuộc về organization
2. **Access Control:** Detailed permission system
3. **Audit Trail:** Complete change history
4. **Data Encryption:** Sensitive fields encrypted
5. **File Security:** Virus scanning, access control

## Migration Strategy

1. **Incremental Migrations:** Từng bảng một theo thứ tự dependency
2. **Data Seeding:** Sample data cho development
3. **Index Creation:** Separate migrations cho performance indexes
4. **Foreign Key Constraints:** Cuối cùng để tránh circular dependencies

## Backup và Recovery

1. **Point-in-time Recovery:** Với transaction logs
2. **Soft Delete Recovery:** Restore deleted records
3. **Version Control:** Template và configuration versioning
4. **Data Archival:** Automatic archival cho old records
