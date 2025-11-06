# 🧪 1Office API Testing Guide

## 📋 Tổng quan

Hướng dẫn chi tiết để test API 1Office sử dụng Postman, cURL và automated testing.

### 🔗 **Setup Environment**
```
Base URL: http://localhost:8000/api
Production: https://api.1office.vn/api
```

---

## 🚀 Quick Start với Postman

### **1. Import Collection & Environment**

#### **Download Files:**
```bash
# Postman Collection
curl -o "1Office_API_Collection.json" "http://localhost:8000/api/postman-collection"

# Postman Environment
curl -o "1Office_API_Environment.json" "http://localhost:8000/api/postman-environment"
```

#### **Import vào Postman:**
1. Mở Postman
2. Click **Import** → **Upload Files**
3. Chọn 2 files đã download
4. Chọn Environment: **1Office API Environment**

### **2. Environment Variables**
```json
{
    "base_url": "http://localhost:8000",
    "auth_token": "",
    "user_email": "user@example.com",
    "user_password": "password123"
}
```

---

## 🔐 Authentication Flow Testing

### **Test Sequence:**

#### **1. Health Check**
```
GET {{base_url}}/api/health
```
**Expected:** Status 200, healthy response

#### **2. Register User**
```
POST {{base_url}}/api/auth/register
{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "device_name": "Postman Client"
}
```
**Expected:** Status 201, token in response
**Auto-save token:** ✅

#### **3. Login**
```
POST {{base_url}}/api/auth/login
{
    "email": "test@example.com",
    "password": "password123",
    "device_name": "Postman Client"
}
```
**Expected:** Status 200, token + device info
**Auto-save token:** ✅

#### **4. Get Current User**
```
GET {{base_url}}/api/auth/me
Authorization: Bearer {{auth_token}}
```
**Expected:** Status 200, user info + statistics

#### **5. Refresh Token**
```
POST {{base_url}}/api/auth/refresh
Authorization: Bearer {{auth_token}}
```
**Expected:** Status 200, new token
**Auto-save new token:** ✅

#### **6. Logout**
```
POST {{base_url}}/api/auth/logout
Authorization: Bearer {{auth_token}}
{
    "all_devices": false
}
```
**Expected:** Status 200, success message
**Auto-clear token:** ✅

---

## 📱 Device Management Testing

### **Test Sequence:**

#### **1. List Active Devices**
```
GET {{base_url}}/api/devices
Authorization: Bearer {{auth_token}}
```
**Expected:** Array of devices with current device marked

#### **2. Get Current Device**
```
GET {{base_url}}/api/devices/current
Authorization: Bearer {{auth_token}}
```
**Expected:** Current device info with is_current: true

#### **3. Device Statistics**
```
GET {{base_url}}/api/devices/statistics
Authorization: Bearer {{auth_token}}
```
**Expected:** Statistics object with counts

#### **4. Set Device Name**
```
PUT {{base_url}}/api/devices/name
Authorization: Bearer {{auth_token}}
{
    "device_name": "My Test Device"
}
```
**Expected:** Success message with updated name

#### **5. Update Activity**
```
POST {{base_url}}/api/devices/activity
Authorization: Bearer {{auth_token}}
```
**Expected:** Activity timestamp updated

#### **6. Check Session**
```
GET {{base_url}}/api/devices/session/check
Authorization: Bearer {{auth_token}}
```
**Expected:** is_valid: true

#### **7. Logout Other Devices**
```
POST {{base_url}}/api/devices/logout-other
Authorization: Bearer {{auth_token}}
```
**Expected:** Success with affected_devices count

---

## 🧪 Automated Testing Scripts

### **Postman Test Scripts:**

#### **Global Pre-request Script:**
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

// Log request details
console.log(`${pm.request.method} ${pm.request.url}`);
```

#### **Global Test Script:**
```javascript
// Basic response tests
pm.test("Response time is less than 2000ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(2000);
});

pm.test("Response has success field", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
});

pm.test("Content-Type is application/json", function () {
    pm.expect(pm.response.headers.get('Content-Type')).to.include('application/json');
});

// Log response
console.log("Response:", pm.response.json());
```

#### **Authentication Test Script:**
```javascript
// For login/register endpoints
if (pm.response.code === 200 || pm.response.code === 201) {
    const response = pm.response.json();
    
    pm.test("Response is successful", function () {
        pm.expect(response.success).to.be.true;
    });
    
    if (response.data && response.data.token) {
        pm.test("Token is present", function () {
            pm.expect(response.data.token).to.be.a('string');
            pm.expect(response.data.token.length).to.be.above(10);
        });
        
        // Save token
        pm.environment.set("auth_token", response.data.token);
        console.log("Token saved:", response.data.token);
    }
    
    if (response.data && response.data.user) {
        pm.test("User data is present", function () {
            pm.expect(response.data.user).to.have.property('id');
            pm.expect(response.data.user).to.have.property('email');
        });
    }
}
```

#### **Device Management Test Script:**
```javascript
// For device endpoints
if (pm.response.code === 200) {
    const response = pm.response.json();
    
    pm.test("Response is successful", function () {
        pm.expect(response.success).to.be.true;
    });
    
    // For device list endpoint
    if (response.data && response.data.devices) {
        pm.test("Devices array is present", function () {
            pm.expect(response.data.devices).to.be.an('array');
        });
        
        pm.test("At least one device exists", function () {
            pm.expect(response.data.devices.length).to.be.above(0);
        });
        
        // Check current device
        const currentDevice = response.data.devices.find(d => d.is_current);
        pm.test("Current device is marked", function () {
            pm.expect(currentDevice).to.exist;
        });
    }
    
    // For statistics endpoint
    if (response.data && response.data.total_sessions !== undefined) {
        pm.test("Statistics data is valid", function () {
            pm.expect(response.data.total_sessions).to.be.a('number');
            pm.expect(response.data.active_sessions).to.be.a('number');
        });
    }
}
```

---

## 🔧 cURL Testing Examples

### **Complete Authentication Flow:**
```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"
EMAIL="test@example.com"
PASSWORD="password123"

echo "🔍 Health Check..."
curl -s "$BASE_URL/health" | jq .

echo "📝 Register User..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "'$EMAIL'",
    "password": "'$PASSWORD'",
    "password_confirmation": "'$PASSWORD'",
    "device_name": "cURL Client"
  }')

echo $REGISTER_RESPONSE | jq .

# Extract token
TOKEN=$(echo $REGISTER_RESPONSE | jq -r '.data.token')
echo "🔑 Token: $TOKEN"

echo "👤 Get Current User..."
curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo "📱 List Devices..."
curl -s -X GET "$BASE_URL/devices" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo "📊 Device Statistics..."
curl -s -X GET "$BASE_URL/devices/statistics" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo "🚪 Logout..."
curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"all_devices": false}' | jq .
```

### **Error Testing:**
```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"

echo "❌ Test Invalid Login..."
curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "invalid@example.com",
    "password": "wrongpassword"
  }' | jq .

echo "❌ Test Unauthorized Access..."
curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer invalid_token" | jq .

echo "❌ Test Validation Error..."
curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "",
    "email": "invalid-email",
    "password": "123"
  }' | jq .
```

---

## 📊 Performance Testing

### **Load Testing với Artillery:**

#### **Install Artillery:**
```bash
npm install -g artillery
```

#### **artillery.yml:**
```yaml
config:
  target: 'http://localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 10
  defaults:
    headers:
      Content-Type: 'application/json'
      Accept: 'application/json'

scenarios:
  - name: "Authentication Flow"
    weight: 70
    flow:
      - post:
          url: "/api/auth/login"
          json:
            email: "test@example.com"
            password: "password123"
            device_name: "Artillery Client"
          capture:
            - json: "$.data.token"
              as: "token"
      - get:
          url: "/api/auth/me"
          headers:
            Authorization: "Bearer {{ token }}"
      - post:
          url: "/api/auth/logout"
          headers:
            Authorization: "Bearer {{ token }}"
          json:
            all_devices: false

  - name: "Device Management"
    weight: 30
    flow:
      - post:
          url: "/api/auth/login"
          json:
            email: "test@example.com"
            password: "password123"
          capture:
            - json: "$.data.token"
              as: "token"
      - get:
          url: "/api/devices"
          headers:
            Authorization: "Bearer {{ token }}"
      - get:
          url: "/api/devices/statistics"
          headers:
            Authorization: "Bearer {{ token }}"
```

#### **Run Load Test:**
```bash
artillery run artillery.yml
```

---

## 🐛 Debugging Tips

### **Common Issues:**

#### **1. Token Issues**
```javascript
// Check token in Postman
console.log("Current token:", pm.environment.get("auth_token"));

// Validate token format
const token = pm.environment.get("auth_token");
if (!token || token.length < 10) {
    console.error("Invalid token!");
}
```

#### **2. Environment Issues**
```javascript
// Check all environment variables
console.log("Environment variables:");
console.log("base_url:", pm.environment.get("base_url"));
console.log("auth_token:", pm.environment.get("auth_token"));
```

#### **3. Response Debugging**
```javascript
// Log full response
console.log("Status:", pm.response.code);
console.log("Headers:", pm.response.headers);
console.log("Body:", pm.response.text());

// Check for specific errors
const response = pm.response.json();
if (!response.success) {
    console.error("API Error:", response.message);
    if (response.errors) {
        console.error("Validation Errors:", response.errors);
    }
}
```

---

## 📋 Test Checklist

### **Authentication Module:**
- [ ] Health check works
- [ ] User registration with validation
- [ ] User login with device tracking
- [ ] Token refresh functionality
- [ ] User logout (single device)
- [ ] User logout (all devices)
- [ ] Get current user info
- [ ] Password reset flow
- [ ] Invalid credentials handling
- [ ] Token expiration handling

### **Device Management:**
- [ ] List active devices
- [ ] Get current device info
- [ ] Device statistics
- [ ] Set device name
- [ ] Update activity
- [ ] Check session validity
- [ ] Refresh session
- [ ] Logout specific device
- [ ] Logout other devices
- [ ] Logout all devices

### **Error Handling:**
- [ ] 401 Unauthorized responses
- [ ] 422 Validation errors
- [ ] 404 Not found errors
- [ ] 500 Server errors
- [ ] Rate limiting responses
- [ ] Malformed request handling

### **Performance:**
- [ ] Response times < 2000ms
- [ ] Concurrent user handling
- [ ] Rate limiting enforcement
- [ ] Memory usage optimization
- [ ] Database query optimization

---

**📞 Support:** support@1office.vn  
**📖 Documentation:** [API Docs](http://localhost:8000/api/documentation)  
**🔗 Postman Collection:** [Download](http://localhost:8000/api/postman-collection)
