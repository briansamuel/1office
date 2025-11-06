<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

    /*
    |--------------------------------------------------------------------------
    | Device Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho hệ thống xác thực thiết bị
    |
    */

    // Số thiết bị tối đa mỗi user có thể đăng nhập cùng lúc
    'max_devices_per_user' => env('AUTH_MAX_DEVICES', 5),

    // Thời gian session tồn tại (phút)
    'session_lifetime' => env('SESSION_LIFETIME', 120),

    // Thời gian không hoạt động trước khi session hết hạn (phút)
    'session_idle_timeout' => env('SESSION_IDLE_TIMEOUT', 30),

    // Có cho phép đăng nhập từ nhiều thiết bị cùng loại không
    'allow_multiple_same_device_type' => env('AUTH_ALLOW_MULTIPLE_SAME_DEVICE', true),

    // Có tự động đăng xuất thiết bị cũ khi vượt quá giới hạn không
    'auto_logout_old_devices' => env('AUTH_AUTO_LOGOUT_OLD_DEVICES', true),

    // Có ghi log hoạt động đăng nhập không
    'log_login_activity' => env('AUTH_LOG_LOGIN_ACTIVITY', true),

    // Có kiểm tra IP thay đổi không
    'check_ip_change' => env('AUTH_CHECK_IP_CHANGE', false),

    // Có yêu cầu xác thực lại khi IP thay đổi không
    'require_reauth_on_ip_change' => env('AUTH_REQUIRE_REAUTH_IP_CHANGE', false),

    // Thời gian lưu trữ session đã logout (ngày)
    'keep_logout_sessions_days' => env('AUTH_KEEP_LOGOUT_SESSIONS_DAYS', 30),

    // Có cho phép nhớ đăng nhập không
    'allow_remember_me' => env('AUTH_ALLOW_REMEMBER_ME', true),

    // Thời gian nhớ đăng nhập (ngày)
    'remember_me_duration' => env('AUTH_REMEMBER_ME_DURATION', 30),

    /*
    |--------------------------------------------------------------------------
    | Device Detection Settings
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho việc phát hiện thiết bị
    |
    */

    'device_detection' => [
        // Có sử dụng GeoIP để xác định vị trí không
        'use_geoip' => env('AUTH_USE_GEOIP', false),
        
        // Service GeoIP (maxmind, ipapi, etc.)
        'geoip_service' => env('AUTH_GEOIP_SERVICE', 'maxmind'),
        
        // Có lưu thông tin User-Agent đầy đủ không
        'store_full_user_agent' => env('AUTH_STORE_FULL_USER_AGENT', true),
        
        // Có tạo device fingerprint không
        'create_device_fingerprint' => env('AUTH_CREATE_DEVICE_FINGERPRINT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Cấu hình bảo mật cho authentication
    |
    */

    'security' => [
        // Có kiểm tra brute force không
        'enable_brute_force_protection' => env('AUTH_BRUTE_FORCE_PROTECTION', true),
        
        // Số lần đăng nhập sai tối đa
        'max_login_attempts' => env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
        
        // Thời gian khóa tài khoản (phút)
        'lockout_duration' => env('AUTH_LOCKOUT_DURATION', 15),
        
        // Có gửi email thông báo đăng nhập mới không
        'notify_new_device_login' => env('AUTH_NOTIFY_NEW_DEVICE', true),
        
        // Có gửi email thông báo đăng nhập bất thường không
        'notify_suspicious_login' => env('AUTH_NOTIFY_SUSPICIOUS_LOGIN', true),
        
        // Có yêu cầu 2FA cho thiết bị mới không
        'require_2fa_new_device' => env('AUTH_REQUIRE_2FA_NEW_DEVICE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho việc dọn dẹp session
    |
    */

    'cleanup' => [
        // Có tự động dọn dẹp session hết hạn không
        'auto_cleanup_expired' => env('AUTH_AUTO_CLEANUP_EXPIRED', true),
        
        // Tần suất dọn dẹp (cron expression)
        'cleanup_schedule' => env('AUTH_CLEANUP_SCHEDULE', '0 2 * * *'), // 2:00 AM daily
        
        // Số ngày giữ lại session đã logout
        'keep_logout_sessions' => env('AUTH_KEEP_LOGOUT_SESSIONS', 30),
        
        // Số ngày giữ lại log hoạt động
        'keep_activity_logs' => env('AUTH_KEEP_ACTIVITY_LOGS', 90),
    ],

];
