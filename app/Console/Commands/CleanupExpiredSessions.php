<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DeviceAuthenticationService;

class CleanupExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:cleanup 
                            {--days=30 : Số ngày để giữ lại session đã hết hạn}
                            {--force : Xóa mà không cần xác nhận}';

    /**
     * The console command description.
     */
    protected $description = 'Dọn dẹp các session đã hết hạn và không hoạt động';

    protected DeviceAuthenticationService $deviceAuthService;

    public function __construct(DeviceAuthenticationService $deviceAuthService)
    {
        parent::__construct();
        $this->deviceAuthService = $deviceAuthService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Bắt đầu dọn dẹp session đã hết hạn...');

        $days = $this->option('days');
        $force = $this->option('force');

        // Đếm số session sẽ bị xóa
        $expiredCount = \App\Models\UserSession::where('expires_at', '<=', now())
                                              ->orWhere(function ($query) use ($days) {
                                                  $query->where('is_active', false)
                                                        ->where('logout_at', '<=', now()->subDays($days));
                                              })
                                              ->count();

        if ($expiredCount === 0) {
            $this->info('✅ Không có session nào cần dọn dẹp.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Tìm thấy {$expiredCount} session cần dọn dẹp.");

        // Xác nhận trước khi xóa (trừ khi có --force)
        if (!$force && !$this->confirm('Bạn có chắc chắn muốn xóa các session này?')) {
            $this->info('❌ Hủy bỏ thao tác dọn dẹp.');
            return self::SUCCESS;
        }

        // Thực hiện dọn dẹp
        $deletedCount = $this->deviceAuthService->cleanupExpiredSessions();

        // Dọn dẹp thêm các session cũ đã logout
        $oldLogoutSessions = \App\Models\UserSession::where('is_active', false)
                                                   ->where('logout_at', '<=', now()->subDays($days))
                                                   ->delete();

        $totalDeleted = $deletedCount + $oldLogoutSessions;

        if ($totalDeleted > 0) {
            $this->info("✅ Đã dọn dẹp thành công {$totalDeleted} session.");
            
            // Hiển thị thống kê
            $this->displayStatistics();
        } else {
            $this->warn('⚠️  Không có session nào được xóa.');
        }

        return self::SUCCESS;
    }

    /**
     * Hiển thị thống kê session
     */
    protected function displayStatistics(): void
    {
        $this->info("\n📊 Thống kê session hiện tại:");

        $totalSessions = \App\Models\UserSession::count();
        $activeSessions = \App\Models\UserSession::where('is_active', true)->count();
        $expiredSessions = \App\Models\UserSession::where('expires_at', '<=', now())->count();

        $this->table(
            ['Loại Session', 'Số lượng'],
            [
                ['Tổng cộng', $totalSessions],
                ['Đang hoạt động', $activeSessions],
                ['Đã hết hạn', $expiredSessions],
                ['Đã đăng xuất', $totalSessions - $activeSessions],
            ]
        );

        // Thống kê theo thiết bị
        $deviceStats = \App\Models\UserSession::selectRaw('device_type, COUNT(*) as count')
                                             ->where('is_active', true)
                                             ->groupBy('device_type')
                                             ->get();

        if ($deviceStats->isNotEmpty()) {
            $this->info("\n📱 Thống kê theo thiết bị (đang hoạt động):");
            $this->table(
                ['Loại thiết bị', 'Số lượng'],
                $deviceStats->map(function ($stat) {
                    return [
                        ucfirst($stat->device_type),
                        $stat->count
                    ];
                })->toArray()
            );
        }
    }
}
