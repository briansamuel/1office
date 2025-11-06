@extends('layouts.app')

@section('title', 'Quản lý thiết bị')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Quản lý thiết bị</h1>
            <p class="text-gray-600">Quản lý các thiết bị đã đăng nhập vào tài khoản của bạn</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-devices text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng thiết bị</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['total_sessions'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Đang hoạt động</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['active_sessions'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tuần này</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['recent_logins'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-map-marker-alt text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Địa chỉ IP</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['unique_ips'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Hành động nhanh</h2>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4">
                    <button onclick="logoutOtherDevices()" 
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Đăng xuất thiết bị khác
                    </button>
                    
                    <button onclick="logoutAllDevices()" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-power-off mr-2"></i>
                        Đăng xuất tất cả thiết bị
                    </button>
                    
                    <button onclick="refreshDevices()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Làm mới
                    </button>
                </div>
            </div>
        </div>

        <!-- Devices List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Thiết bị đang đăng nhập</h2>
            </div>
            <div class="divide-y divide-gray-200" id="devices-list">
                @forelse($devices as $device)
                <div class="p-6 device-item" data-session-token="{{ $device['session_token'] }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Device Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="{{ $device['device_icon'] }} text-xl text-gray-600"></i>
                                </div>
                            </div>
                            
                            <!-- Device Info -->
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $device['device_info'] }}</h3>
                                    @if($device['is_current'])
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                            Thiết bị hiện tại
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="mt-1 text-sm text-gray-500">
                                    <div class="flex items-center space-x-4">
                                        <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $device['ip_address'] }}</span>
                                        @if($device['location'])
                                            <span><i class="fas fa-globe mr-1"></i>{{ $device['location'] }}</span>
                                        @endif
                                        <span><i class="fas fa-calendar mr-1"></i>{{ $device['login_at'] }}</span>
                                    </div>
                                </div>
                                
                                <div class="mt-2 flex items-center space-x-2">
                                    <span class="flex items-center {{ $device['status_color'] }}">
                                        <i class="fas fa-circle text-xs mr-2"></i>
                                        {{ $device['status_text'] }}
                                    </span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-sm text-gray-500">{{ $device['last_activity'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex-shrink-0">
                            @if(!$device['is_current'])
                                <button onclick="logoutDevice('{{ $device['session_token'] }}')" 
                                        class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded-lg text-sm transition duration-200">
                                    <i class="fas fa-sign-out-alt mr-1"></i>
                                    Đăng xuất
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center">
                    <div class="text-gray-400 mb-2">
                        <i class="fas fa-mobile-alt text-4xl"></i>
                    </div>
                    <p class="text-gray-500">Không có thiết bị nào đang đăng nhập</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loading-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6">
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700">Đang xử lý...</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Hiển thị loading
function showLoading() {
    document.getElementById('loading-modal').classList.remove('hidden');
    document.getElementById('loading-modal').classList.add('flex');
}

// Ẩn loading
function hideLoading() {
    document.getElementById('loading-modal').classList.add('hidden');
    document.getElementById('loading-modal').classList.remove('flex');
}

// Đăng xuất thiết bị cụ thể
async function logoutDevice(sessionToken) {
    if (!confirm('Bạn có chắc chắn muốn đăng xuất khỏi thiết bị này?')) {
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('/auth/devices/logout-device', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ session_token: sessionToken })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Xóa thiết bị khỏi danh sách
            const deviceItem = document.querySelector(`[data-session-token="${sessionToken}"]`);
            if (deviceItem) {
                deviceItem.remove();
            }
            
            showNotification(result.message, 'success');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('Có lỗi xảy ra', 'error');
    } finally {
        hideLoading();
    }
}

// Đăng xuất thiết bị khác
async function logoutOtherDevices() {
    if (!confirm('Bạn có chắc chắn muốn đăng xuất khỏi tất cả thiết bị khác?')) {
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('/auth/devices/logout-other', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('Có lỗi xảy ra', 'error');
    } finally {
        hideLoading();
    }
}

// Đăng xuất tất cả thiết bị
async function logoutAllDevices() {
    if (!confirm('Bạn có chắc chắn muốn đăng xuất khỏi tất cả thiết bị? Bạn sẽ cần đăng nhập lại.')) {
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('/auth/devices/logout-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => {
                window.location.href = result.redirect || '/login';
            }, 1500);
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('Có lỗi xảy ra', 'error');
    } finally {
        hideLoading();
    }
}

// Làm mới danh sách thiết bị
function refreshDevices() {
    location.reload();
}

// Hiển thị thông báo
function showNotification(message, type = 'info') {
    // Tạo notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Tự động xóa sau 3 giây
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Tự động làm mới hoạt động
setInterval(async () => {
    try {
        await fetch('/auth/devices/update-activity', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
    } catch (error) {
        console.log('Failed to update activity');
    }
}, 60000); // Mỗi phút
</script>
@endpush
