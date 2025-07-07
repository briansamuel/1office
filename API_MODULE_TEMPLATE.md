# 📚 [MODULE_NAME] API Documentation

## 📋 Tổng quan

**[MODULE_NAME] Module** cung cấp các API endpoints cho [mô tả chức năng module].

### 🔗 **Base URL**
```
Production: https://api.1office.vn/api/[module_prefix]
Development: http://localhost:8000/api/[module_prefix]
```

### 🔑 **Authentication**
Tất cả endpoints đều yêu cầu authentication:
```
Authorization: Bearer {token}
```

### 📊 **Response Format**
```json
{
    "success": true|false,
    "message": "Thông báo",
    "data": {}, // Dữ liệu (nếu có)
    "errors": {} // Lỗi validation (nếu có)
}
```

---

## 📋 Endpoints

### **1. [Endpoint Name]**

**`[METHOD]`** `/api/[module_prefix]/[endpoint]`

[Mô tả endpoint]

#### **Request:**
```json
{
    "field1": "value1",
    "field2": "value2"
}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Thành công",
    "data": {
        "id": 1,
        "field1": "value1",
        "field2": "value2",
        "created_at": "2024-01-20T10:00:00.000000Z"
    }
}
```

#### **Postman Setup:**
```
Method: [METHOD]
URL: {{base_url}}/api/[module_prefix]/[endpoint]
Headers:
  Authorization: Bearer {{auth_token}}
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
    "field1": "value1",
    "field2": "value2"
}
```

#### **cURL:**
```bash
curl -X [METHOD] "http://localhost:8000/api/[module_prefix]/[endpoint]" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "field1": "value1",
    "field2": "value2"
  }'
```

---

### **2. [Another Endpoint]**

**`[METHOD]`** `/api/[module_prefix]/[endpoint]`

[Mô tả endpoint]

#### **URL Parameters:**
- `id` (integer): ID của resource

#### **Query Parameters:**
- `page` (integer, optional): Số trang (default: 1)
- `per_page` (integer, optional): Số items per page (default: 15, max: 100)
- `sort` (string, optional): Trường sort (default: created_at)
- `order` (string, optional): Thứ tự sort (asc|desc, default: desc)
- `filter[field]` (string, optional): Filter theo field

#### **Response (200):**
```json
{
    "success": true,
    "message": "Lấy danh sách thành công",
    "data": {
        "items": [
            {
                "id": 1,
                "field1": "value1",
                "field2": "value2",
                "created_at": "2024-01-20T10:00:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "last_page": 7,
            "from": 1,
            "to": 15
        }
    }
}
```

---

## 🔧 Common Endpoints

### **List Resources**
**`GET`** `/api/[module_prefix]/[resources]`

#### **Query Parameters:**
- `page` (integer): Số trang
- `per_page` (integer): Số items per page
- `sort` (string): Trường sort
- `order` (string): asc|desc
- `search` (string): Tìm kiếm
- `filter[field]` (string): Filter theo field

### **Create Resource**
**`POST`** `/api/[module_prefix]/[resources]`

### **Get Resource**
**`GET`** `/api/[module_prefix]/[resources]/{id}`

### **Update Resource**
**`PUT`** `/api/[module_prefix]/[resources]/{id}`

### **Delete Resource**
**`DELETE`** `/api/[module_prefix]/[resources]/{id}`

---

## ⚠️ Error Responses

### **Validation Error (422):**
```json
{
    "success": false,
    "message": "Dữ liệu không hợp lệ",
    "errors": {
        "field1": ["Field1 là bắt buộc"],
        "field2": ["Field2 phải là số"]
    }
}
```

### **Not Found (404):**
```json
{
    "success": false,
    "message": "Không tìm thấy resource"
}
```

### **Forbidden (403):**
```json
{
    "success": false,
    "message": "Không có quyền truy cập"
}
```

---

## 📋 Postman Collection

### **Environment Variables:**
```json
{
    "base_url": "http://localhost:8000",
    "auth_token": "",
    "[module_prefix]_id": ""
}
```

### **Collection Structure:**
```
📁 [MODULE_NAME] Module
├── 📄 List [Resources]
├── 📄 Create [Resource]
├── 📄 Get [Resource]
├── 📄 Update [Resource]
├── 📄 Delete [Resource]
└── 📁 Advanced
    ├── 📄 Search [Resources]
    ├── 📄 Filter [Resources]
    └── 📄 Bulk Operations
```

### **Pre-request Script:**
```javascript
// Auto-set headers
pm.request.headers.add({
    key: 'Accept',
    value: 'application/json'
});

if (pm.request.method === 'POST' || pm.request.method === 'PUT') {
    pm.request.headers.add({
        key: 'Content-Type',
        value: 'application/json'
    });
}
```

### **Test Script:**
```javascript
// Test response status
pm.test("Status code is successful", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});

// Test response format
pm.test("Response has success field", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
});

// Save ID for other requests
if (pm.response.code === 201) {
    const response = pm.response.json();
    if (response.data && response.data.id) {
        pm.environment.set("[module_prefix]_id", response.data.id);
    }
}
```

---

## 🔒 Permissions

### **Required Permissions:**
- `[module_prefix].read` - Xem danh sách và chi tiết
- `[module_prefix].create` - Tạo mới
- `[module_prefix].update` - Cập nhật
- `[module_prefix].delete` - Xóa
- `[module_prefix].manage` - Quản lý toàn bộ

### **Role-based Access:**
- **Admin:** Tất cả permissions
- **Manager:** read, create, update
- **User:** read only
- **[Custom Role]:** [specific permissions]

---

## 📊 Rate Limiting

- **Authenticated Users:** 1000 requests/minute
- **Unauthenticated:** 60 requests/minute
- **Bulk Operations:** 100 requests/minute

---

## 🔍 Search & Filter

### **Search:**
```
GET /api/[module_prefix]/[resources]?search=keyword
```

### **Filter:**
```
GET /api/[module_prefix]/[resources]?filter[status]=active&filter[type]=premium
```

### **Sort:**
```
GET /api/[module_prefix]/[resources]?sort=created_at&order=desc
```

### **Pagination:**
```
GET /api/[module_prefix]/[resources]?page=2&per_page=20
```

---

## 📝 Notes

### **Data Validation:**
- Tất cả input đều được validate
- Required fields được đánh dấu rõ ràng
- Format validation cho email, phone, date, etc.

### **Performance:**
- Pagination mặc định: 15 items/page
- Maximum: 100 items/page
- Eager loading cho relationships
- Database indexing cho search fields

### **Security:**
- CSRF protection
- SQL injection prevention
- XSS protection
- Rate limiting
- Permission-based access control

---

**📞 Support:** support@1office.vn  
**📖 Full Documentation:** [API Docs](http://localhost:8000/api/documentation)  
**🔗 Postman Collection:** [Download](http://localhost:8000/api/postman-collection)

---

## 📋 Checklist cho Module mới

### **Backend Implementation:**
- [ ] Controller với CRUD operations
- [ ] Model với relationships
- [ ] Validation rules
- [ ] Permissions & policies
- [ ] Database migrations
- [ ] API routes
- [ ] Tests (Unit & Feature)

### **Documentation:**
- [ ] API endpoints documented
- [ ] Request/Response examples
- [ ] Postman collection
- [ ] Error handling
- [ ] Permissions documented
- [ ] Rate limiting info

### **Testing:**
- [ ] Unit tests
- [ ] Feature tests
- [ ] API tests
- [ ] Postman tests
- [ ] Performance tests

### **Security:**
- [ ] Authentication required
- [ ] Authorization implemented
- [ ] Input validation
- [ ] Rate limiting
- [ ] CSRF protection

---

**Template này nên được sử dụng cho mỗi module mới để đảm bảo tính nhất quán trong documentation!** 📚
