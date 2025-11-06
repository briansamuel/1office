<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PostmanController extends Controller
{
    /**
     * Generate Postman Collection for 1Office API
     */
    public function collection(): JsonResponse
    {
        $collection = [
            "info" => [
                "name" => "1Office API Collection",
                "description" => "Comprehensive API collection for 1Office platform with authentication and device management",
                "version" => "1.0.0",
                "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            ],
            "auth" => [
                "type" => "bearer",
                "bearer" => [
                    [
                        "key" => "token",
                        "value" => "{{auth_token}}",
                        "type" => "string"
                    ]
                ]
            ],
            "event" => [
                [
                    "listen" => "prerequest",
                    "script" => [
                        "type" => "text/javascript",
                        "exec" => [
                            "// Auto-set headers for all requests",
                            "pm.request.headers.add({",
                            "    key: 'Accept',",
                            "    value: 'application/json'",
                            "});",
                            "",
                            "if (pm.request.method === 'POST' || pm.request.method === 'PUT') {",
                            "    pm.request.headers.add({",
                            "        key: 'Content-Type',",
                            "        value: 'application/json'",
                            "    });",
                            "}"
                        ]
                    ]
                ],
                [
                    "listen" => "test",
                    "script" => [
                        "type" => "text/javascript",
                        "exec" => [
                            "// Global test scripts",
                            "pm.test('Response time is less than 2000ms', function () {",
                            "    pm.expect(pm.response.responseTime).to.be.below(2000);",
                            "});",
                            "",
                            "pm.test('Response has success field', function () {",
                            "    const jsonData = pm.response.json();",
                            "    pm.expect(jsonData).to.have.property('success');",
                            "});",
                            "",
                            "// Log response for debugging",
                            "console.log('Response:', pm.response.json());"
                        ]
                    ]
                ]
            ],
            "variable" => [
                [
                    "key" => "base_url",
                    "value" => config('app.url') . '/api',
                    "type" => "string"
                ],
                [
                    "key" => "auth_token",
                    "value" => "",
                    "type" => "string"
                ]
            ],
            "item" => [
                // API Information folder
                [
                    "name" => "🔧 API Information",
                    "item" => [
                        [
                            "name" => "API Info",
                            "request" => [
                                "method" => "GET",
                                "header" => [],
                                "url" => [
                                    "raw" => "{{base_url}}",
                                    "host" => ["{{base_url}}"]
                                ],
                                "description" => "Get API information and available endpoints"
                            ]
                        ],
                        [
                            "name" => "Health Check",
                            "request" => [
                                "method" => "GET",
                                "header" => [],
                                "url" => [
                                    "raw" => "{{base_url}}/health",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["health"]
                                ],
                                "description" => "Check API health status"
                            ]
                        ]
                    ]
                ],
                
                // Authentication folder
                [
                    "name" => "🔐 Authentication",
                    "item" => [
                        [
                            "name" => "Register",
                            "event" => [
                                [
                                    "listen" => "test",
                                    "script" => [
                                        "type" => "text/javascript",
                                        "exec" => [
                                            "if (pm.response.code === 201) {",
                                            "    const response = pm.response.json();",
                                            "    if (response.success && response.data.token) {",
                                            "        pm.environment.set('auth_token', response.data.token);",
                                            "        console.log('Token saved:', response.data.token);",
                                            "    }",
                                            "}"
                                        ]
                                    ]
                                ]
                            ],
                            "request" => [
                                "method" => "POST",
                                "header" => [],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "name" => "Nguyễn Văn A",
                                        "email" => "user@example.com",
                                        "password" => "password123",
                                        "password_confirmation" => "password123",
                                        "phone" => "0123456789",
                                        "device_name" => "Postman Client"
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/register",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "register"]
                                ],
                                "description" => "Register new user account with device tracking"
                            ]
                        ],
                        [
                            "name" => "Login",
                            "event" => [
                                [
                                    "listen" => "test",
                                    "script" => [
                                        "type" => "text/javascript",
                                        "exec" => [
                                            "if (pm.response.code === 200) {",
                                            "    const response = pm.response.json();",
                                            "    if (response.success && response.data.token) {",
                                            "        pm.environment.set('auth_token', response.data.token);",
                                            "        console.log('Token saved:', response.data.token);",
                                            "    }",
                                            "}"
                                        ]
                                    ]
                                ]
                            ],
                            "request" => [
                                "method" => "POST",
                                "header" => [],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "email" => "user@example.com",
                                        "password" => "password123",
                                        "device_name" => "Postman Client"
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/login",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "login"]
                                ],
                                "description" => "Login with email and password"
                            ]
                        ],
                        [
                            "name" => "Get Current User",
                            "request" => [
                                "method" => "GET",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/me",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "me"]
                                ],
                                "description" => "Get current authenticated user information"
                            ]
                        ],
                        [
                            "name" => "Refresh Token",
                            "event" => [
                                [
                                    "listen" => "test",
                                    "script" => [
                                        "type" => "text/javascript",
                                        "exec" => [
                                            "if (pm.response.code === 200) {",
                                            "    const response = pm.response.json();",
                                            "    if (response.success && response.data.token) {",
                                            "        pm.environment.set('auth_token', response.data.token);",
                                            "        console.log('New token saved:', response.data.token);",
                                            "    }",
                                            "}"
                                        ]
                                    ]
                                ]
                            ],
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "device_name" => "Postman Client Refreshed"
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/refresh",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "refresh"]
                                ],
                                "description" => "Refresh authentication token"
                            ]
                        ],
                        [
                            "name" => "Logout",
                            "event" => [
                                [
                                    "listen" => "test",
                                    "script" => [
                                        "type" => "text/javascript",
                                        "exec" => [
                                            "if (pm.response.code === 200) {",
                                            "    pm.environment.unset('auth_token');",
                                            "    console.log('Token removed from environment');",
                                            "}"
                                        ]
                                    ]
                                ]
                            ],
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "all_devices" => false
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/logout",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "logout"]
                                ],
                                "description" => "Logout from current or all devices"
                            ]
                        ],
                        [
                            "name" => "Forgot Password",
                            "request" => [
                                "method" => "POST",
                                "header" => [],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "email" => "user@example.com"
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/forgot-password",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "forgot-password"]
                                ],
                                "description" => "Send password reset link to email"
                            ]
                        ],
                        [
                            "name" => "Reset Password",
                            "request" => [
                                "method" => "POST",
                                "header" => [],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "token" => "reset_token_here",
                                        "email" => "user@example.com",
                                        "password" => "newpassword123",
                                        "password_confirmation" => "newpassword123"
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/auth/reset-password",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["auth", "reset-password"]
                                ],
                                "description" => "Reset password with token from email"
                            ]
                        ]
                    ]
                ],
                
                // Device Management folder
                [
                    "name" => "📱 Device Management",
                    "item" => [
                        [
                            "name" => "List Active Devices",
                            "request" => [
                                "method" => "GET",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices"]
                                ],
                                "description" => "Get list of all active devices"
                            ]
                        ],
                        [
                            "name" => "Get Current Device",
                            "request" => [
                                "method" => "GET",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/current",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "current"]
                                ],
                                "description" => "Get current device information"
                            ]
                        ],
                        [
                            "name" => "Device Statistics",
                            "request" => [
                                "method" => "GET",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/statistics",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "statistics"]
                                ],
                                "description" => "Get device usage statistics"
                            ]
                        ],
                        [
                            "name" => "Logout Specific Device",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/logout/session_token_here",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "logout", "session_token_here"]
                                ],
                                "description" => "Logout from specific device by session token"
                            ]
                        ],
                        [
                            "name" => "Logout Other Devices",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/logout-other",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "logout-other"]
                                ],
                                "description" => "Logout from all other devices except current"
                            ]
                        ],
                        [
                            "name" => "Logout All Devices",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/logout-all",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "logout-all"]
                                ],
                                "description" => "Logout from all devices including current"
                            ]
                        ],
                        [
                            "name" => "Set Device Name",
                            "request" => [
                                "method" => "PUT",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "device_name" => "My Custom Device Name"
                                    ], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/name",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "name"]
                                ],
                                "description" => "Set custom name for current device"
                            ]
                        ],
                        [
                            "name" => "Update Activity",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/activity",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "activity"]
                                ],
                                "description" => "Update session activity timestamp"
                            ]
                        ],
                        [
                            "name" => "Check Session",
                            "request" => [
                                "method" => "GET",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/session/check",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "session", "check"]
                                ],
                                "description" => "Check if current session is valid"
                            ]
                        ],
                        [
                            "name" => "Refresh Session",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    [
                                        "key" => "Authorization",
                                        "value" => "Bearer {{auth_token}}",
                                        "type" => "text"
                                    ]
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/devices/session/refresh",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["devices", "session", "refresh"]
                                ],
                                "description" => "Refresh current session"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($collection, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="1Office_API_Collection.json"'
        ]);
    }

    /**
     * Generate Postman Environment
     */
    public function environment(): JsonResponse
    {
        $environment = [
            "id" => "1office-api-environment",
            "name" => "1Office API Environment",
            "values" => [
                [
                    "key" => "base_url",
                    "value" => config('app.url') . '/api',
                    "enabled" => true,
                    "type" => "default"
                ],
                [
                    "key" => "auth_token",
                    "value" => "",
                    "enabled" => true,
                    "type" => "secret"
                ],
                [
                    "key" => "user_email",
                    "value" => "user@example.com",
                    "enabled" => true,
                    "type" => "default"
                ],
                [
                    "key" => "user_password",
                    "value" => "password123",
                    "enabled" => true,
                    "type" => "secret"
                ]
            ],
            "_postman_variable_scope" => "environment"
        ];

        return response()->json($environment, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="1Office_API_Environment.json"'
        ]);
    }
}
