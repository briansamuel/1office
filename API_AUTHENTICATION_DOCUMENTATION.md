# 📚 1Office API - Authentication Module Documentation

## 📋 Tổng quan

**Authentication Module** cung cấp đầy đủ các tính năng xác thực người dùng với device tracking và session management.

### 🔗 **Base URL**
```
Production: https://api.1office.vn
Development: http://localhost:8000/api
```

### 🔑 **Authentication Method**
- **Type:** Bearer Token (Laravel Sanctum)
- **Header:** `Authorization: Bearer {token}`

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

## 🔐 Authentication Endpoints

### **1. Đăng ký tài khoản**

**`POST`** `/api/auth/register`

Tạo tài khoản mới với device tracking.

#### **Request:**
```json
{
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "0123456789",
    "device_name": "iPhone 13"
}
```

#### **Response (201):**
```json
{
    "success": true,
    "message": "Đăng ký thành công",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyễn Văn A",
            "email": "user@example.com",
            "phone": "0123456789",
            "created_at": "2024-01-20T10:00:00.000000Z"
        },
        "token": "1|abc123def456...",
        "device_info": {
            "device_type": "mobile",
            "device_name": "iPhone 13",
            "browser": "Safari",
            "platform": "iOS"
        },
        "expires_at": "2024-02-20T10:00:00.000000Z"
    }
}
```

#### **Postman Setup:**
```
Method: POST
URL: {{base_url}}/api/auth/register
Headers:
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "0123456789",
    "device_name": "iPhone 13"
}
```

#### **cURL:**
```bash
curl -X POST "http://localhost:8000/api/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "0123456789",
    "device_name": "iPhone 13"
  }'
```

---

### **2. Đăng nhập**

**`POST`** `/api/auth/login`

Đăng nhập với device tracking và session management.

#### **Request:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "remember": false,
    "device_name": "MacBook Pro"
}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Đăng nhập thành công",
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyễn Văn A",
            "email": "user@example.com",
            "phone": "0123456789",
            "email_verified_at": "2024-01-20T10:00:00.000000Z",
            "created_at": "2024-01-20T10:00:00.000000Z"
        },
        "token": "2|xyz789abc123...",
        "device_info": {
            "device_type": "desktop",
            "device_name": "MacBook Pro",
            "browser": "Chrome",
            "platform": "macOS",
            "ip_address": "192.168.1.100"
        },
        "session": {
            "id": 5,
            "device_type": "desktop",
            "ip_address": "192.168.1.100",
            "login_at": "2024-01-20T10:00:00.000000Z",
            "expires_at": "2024-01-20T12:00:00.000000Z"
        },
        "expires_at": "2024-02-20T10:00:00.000000Z"
    }
}
```

#### **Postman Setup:**
```
Method: POST
URL: {{base_url}}/api/auth/login
Headers:
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "MacBook Pro"
}

Tests (JavaScript):
if (pm.response.code === 200) {
    const response = pm.response.json();
    if (response.success && response.data.token) {
        pm.environment.set("auth_token", response.data.token);
        console.log("Token saved:", response.data.token);
    }
}
```

#### **cURL:**
```bash
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "MacBook Pro"
  }'
```

---

### **3. Thông tin user hiện tại**

**`GET`** `/api/auth/me`

Lấy thông tin chi tiết của user đang đăng nhập.

#### **Headers:**
```
Authorization: Bearer {token}
```

#### **Response (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Nguyễn Văn A",
            "email": "user@example.com",
            "phone": "0123456789",
            "email_verified_at": "2024-01-20T10:00:00.000000Z",
            "created_at": "2024-01-20T10:00:00.000000Z",
            "updated_at": "2024-01-20T10:00:00.000000Z"
        },
        "current_session": "abc123def456",
        "device_statistics": {
            "total_sessions": 5,
            "active_sessions": 3,
            "device_types": {
                "mobile": 2,
                "desktop": 1
            },
            "recent_logins": 2,
            "unique_ips": 2
        }
    }
}
```

#### **Postman Setup:**
```
Method: GET
URL: {{base_url}}/api/auth/me
Headers:
  Authorization: Bearer {{auth_token}}
  Accept: application/json
```

#### **cURL:**
```bash
curl -X GET "http://localhost:8000/api/auth/me" \
  -H "Authorization: Bearer 2|xyz789abc123..." \
  -H "Accept: application/json"
```

---

### **4. Đăng xuất**

**`POST`** `/api/auth/logout`

Đăng xuất khỏi thiết bị hiện tại hoặc tất cả thiết bị.

#### **Headers:**
```
Authorization: Bearer {token}
```

#### **Request (Optional):**
```json
{
    "all_devices": false
}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Đăng xuất thành công"
}
```

#### **Postman Setup:**
```
Method: POST
URL: {{base_url}}/api/auth/logout
Headers:
  Authorization: Bearer {{auth_token}}
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
    "all_devices": false
}

Tests (JavaScript):
if (pm.response.code === 200) {
    pm.environment.unset("auth_token");
    console.log("Token removed from environment");
}
```

---

### **5. Làm mới token**

**`POST`** `/api/auth/refresh`

Tạo token mới và vô hiệu hóa token cũ.

#### **Headers:**
```
Authorization: Bearer {token}
```

#### **Request (Optional):**
```json
{
    "device_name": "New Device Name"
}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Token đã được làm mới",
    "data": {
        "token": "3|new789token123...",
        "expires_at": "2024-02-20T10:00:00.000000Z"
    }
}
```

#### **Postman Setup:**
```
Method: POST
URL: {{base_url}}/api/auth/refresh
Headers:
  Authorization: Bearer {{auth_token}}
  Content-Type: application/json
  Accept: application/json

Tests (JavaScript):
if (pm.response.code === 200) {
    const response = pm.response.json();
    if (response.success && response.data.token) {
        pm.environment.set("auth_token", response.data.token);
        console.log("New token saved:", response.data.token);
    }
}
```

---

### **6. Quên mật khẩu**

**`POST`** `/api/auth/forgot-password`

Gửi link reset mật khẩu qua email.

#### **Request:**
```json
{
    "email": "user@example.com"
}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Link reset mật khẩu đã được gửi đến email của bạn"
}
```

#### **Postman Setup:**
```
Method: POST
URL: {{base_url}}/api/auth/forgot-password
Headers:
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
    "email": "user@example.com"
}
```

---

### **7. Reset mật khẩu**

**`POST`** `/api/auth/reset-password`

Reset mật khẩu với token từ email.

#### **Request:**
```json
{
    "token": "reset_token_here",
    "email": "user@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Mật khẩu đã được reset thành công"
}
```

---

## 📱 Device Management Endpoints

### **1. Danh sách thiết bị**

**`GET`** `/api/devices`

Lấy tất cả thiết bị đang đăng nhập.

#### **Headers:**
```
Authorization: Bearer {token}
```

#### **Response (200):**
```json
{
    "success": true,
    "message": "Lấy danh sách thiết bị thành công",
    "data": {
        "devices": [
            {
                "id": 1,
                "session_token": "abc123def456",
                "device_info": "Chrome - macOS - MacBook Pro",
                "device_type": "desktop",
                "device_icon": "fas fa-desktop",
                "ip_address": "192.168.1.100",
                "location": null,
                "last_activity": "2 phút trước",
                "login_at": "20/01/2024 10:00",
                "status_color": "text-green-500",
                "status_text": "Đang hoạt động",
                "is_current": true
            }
        ],
        "total": 1
    }
}
```

#### **Postman Setup:**
```
Method: GET
URL: {{base_url}}/api/devices
Headers:
  Authorization: Bearer {{auth_token}}
  Accept: application/json
```

---

### **2. Thiết bị hiện tại**

**`GET`** `/api/devices/current`

Lấy thông tin thiết bị đang sử dụng.

#### **Response (200):**
```json
{
    "success": true,
    "message": "Lấy thông tin thiết bị hiện tại thành công",
    "data": {
        "id": 1,
        "session_token": "abc123def456",
        "device_info": "Chrome - macOS - MacBook Pro",
        "device_type": "desktop",
        "is_current": true
    }
}
```

---

### **3. Thống kê thiết bị**

**`GET`** `/api/devices/statistics`

Lấy thống kê về các thiết bị đã đăng nhập.

#### **Response (200):**
```json
{
    "success": true,
    "message": "Lấy thống kê thành công",
    "data": {
        "total_sessions": 5,
        "active_sessions": 3,
        "device_types": {
            "mobile": 2,
            "desktop": 1,
            "tablet": 0
        },
        "recent_logins": 2,
        "unique_ips": 2
    }
}
```

---

### **4. Đăng xuất thiết bị cụ thể**

**`POST`** `/api/devices/logout/{sessionToken}`

Đăng xuất từ một thiết bị cụ thể.

#### **URL Parameters:**
- `sessionToken`: Session token của thiết bị cần đăng xuất

#### **Response (200):**
```json
{
    "success": true,
    "message": "Đã đăng xuất khỏi thiết bị thành công"
}
```

#### **Postman Setup:**
```
Method: POST
URL: {{base_url}}/api/devices/logout/xyz789ghi012
Headers:
  Authorization: Bearer {{auth_token}}
  Accept: application/json
```

---

### **5. Đăng xuất thiết bị khác**

**`POST`** `/api/devices/logout-other`

Đăng xuất khỏi tất cả thiết bị khác (trừ thiết bị hiện tại).

#### **Response (200):**
```json
{
    "success": true,
    "message": "Đã đăng xuất khỏi 2 thiết bị khác",
    "affected_devices": 2
}
```

---

### **6. Đăng xuất tất cả thiết bị**

**`POST`** `/api/devices/logout-all`

Đăng xuất khỏi tất cả thiết bị (bao gồm thiết bị hiện tại).

#### **Response (200):**
```json
{
    "success": true,
    "message": "Đã đăng xuất khỏi tất cả 3 thiết bị",
    "data": {
        "affected_devices": 3,
        "redirect_to_login": true
    }
}
```

---

## 🔧 Utility Endpoints

### **1. Thông tin API**

**`GET`** `/api`

Lấy thông tin tổng quan về API.

#### **Response (200):**
```json
{
    "name": "1Office API",
    "version": "1.0.0",
    "description": "Comprehensive Business Management Platform API",
    "status": "active",
    "modules": {
        "authentication": "User authentication and device management",
        "work": "Task and project management"
    }
}
```

---

### **2. Health Check**

**`GET`** `/api/health`

Kiểm tra trạng thái hệ thống.

#### **Response (200):**
```json
{
    "status": "healthy",
    "timestamp": "2024-01-20T10:30:00.000000Z",
    "version": "1.0.0",
    "environment": "local",
    "services": {
        "database": "connected",
        "cache": "connected",
        "storage": "available"
    }
}
```

---

## ⚠️ Error Responses

### **Validation Error (422):**
```json
{
    "success": false,
    "message": "Dữ liệu không hợp lệ",
    "errors": {
        "email": ["Email đã tồn tại"],
        "password": ["Mật khẩu phải có ít nhất 8 ký tự"]
    }
}
```

### **Unauthorized (401):**
```json
{
    "success": false,
    "message": "Chưa xác thực"
}
```

### **Not Found (404):**
```json
{
    "success": false,
    "message": "API endpoint not found"
}
```

### **Server Error (500):**
```json
{
    "success": false,
    "message": "Có lỗi xảy ra",
    "error": "Internal server error"
}
```

---

## 📋 Postman Collection Setup

### **Environment Variables:**
```json
{
    "base_url": "http://localhost:8000",
    "auth_token": ""
}
```

### **Pre-request Script (Global):**
```javascript
// Auto-set content type for POST requests
if (pm.request.method === 'POST' || pm.request.method === 'PUT') {
    pm.request.headers.add({
        key: 'Content-Type',
        value: 'application/json'
    });
}

// Auto-set accept header
pm.request.headers.add({
    key: 'Accept',
    value: 'application/json'
});
```

### **Test Script (Global):**
```javascript
// Log response for debugging
console.log("Response:", pm.response.json());

// Check if response is successful
pm.test("Status code is successful", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});

// Check response format
pm.test("Response has success field", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
});
```

---

## 🔒 Security Notes

### **Token Management:**
- Token có thời hạn: 365 ngày (có thể cấu hình)
- Tự động refresh khi gần hết hạn
- Revoke token khi đăng xuất

### **Rate Limiting:**
- 60 requests/minute cho IP chưa xác thực
- 1000 requests/minute cho user đã xác thực

### **Device Security:**
- Giới hạn 5 thiết bị/user (có thể cấu hình)
- Tự động đăng xuất thiết bị cũ khi vượt quá
- Tracking IP và User-Agent

---

**📞 Support:** support@1office.vn  
**📖 Full Documentation:** [API Docs](http://localhost:8000/api/documentation)  
**🔗 Postman Collection:** [Download](http://localhost:8000/api/postman-collection)
