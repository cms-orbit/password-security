<?php

namespace CmsOrbit\PasswordSecurity\Commands;

use CmsOrbit\PasswordSecurity\Notifications\PasswordExpirationNotification;
use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;
use Illuminate\Console\Command;

class NotifyPasswordExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-security:notify-expiration 
                            {--dry-run : 실제 알림 발송 없이 대상만 확인}
                            {--force-change : 만료된 계정에 강제 변경 플래그 설정}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '패스워드 만료 예정 및 만료된 사용자에게 알림을 발송합니다';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('password-security.enabled') || !config('password-security.expiration.enabled')) {
            $this->info('패스워드 만료 관리가 비활성화되어 있습니다.');
            return self::SUCCESS;
        }

        if (!config('password-security.notifications.enabled')) {
            $this->info('알림 기능이 비활성화되어 있습니다.');
            return self::SUCCESS;
        }

        $this->info('패스워드 만료 대상 검색 중...');

        $models = config('password-security.models', []);
        $totalNotified = 0;
        $totalExpired = 0;

        foreach ($models as $modelClass => $settings) {
            if (!class_exists($modelClass)) {
                $this->warn("모델을 찾을 수 없습니다: {$modelClass}");
                continue;
            }

            $this->line("처리 중: {$modelClass}");

            // 만료 예정 사용자 찾기
            $expiringUsers = $this->findExpiringUsers($modelClass);
            
            // 만료된 사용자 찾기
            $expiredUsers = $this->findExpiredUsers($modelClass);

            if ($this->option('dry-run')) {
                $this->info("  만료 예정: {$expiringUsers->count()}명");
                $this->info("  만료됨: {$expiredUsers->count()}명");
                continue;
            }

            // 만료 예정 알림
            foreach ($expiringUsers as $user) {
                $this->notifyExpiringUser($user);
                $totalNotified++;
            }

            // 만료 알림
            foreach ($expiredUsers as $user) {
                $this->notifyExpiredUser($user);
                
                // 강제 변경 플래그 설정
                if ($this->option('force-change') && $user->passwordSecurity) {
                    $user->passwordSecurity->password_must_change = true;
                    $user->passwordSecurity->save();
                }
                
                $totalExpired++;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run 모드: 실제 알림 발송 없음');
        } else {
            $this->info("완료: {$totalNotified}명 만료 예정 알림, {$totalExpired}명 만료 알림");
        }

        return self::SUCCESS;
    }

    /**
     * 만료 예정 사용자 찾기
     */
    protected function findExpiringUsers(string $modelClass)
    {
        $notifyBeforeDays = config('password-security.expiration.notify_before_days', [7, 3, 1]);
        
        $targets = collect();

        foreach ($notifyBeforeDays as $days) {
            $targetDate = now()->addDays($days)->startOfDay();
            $nextDate = $targetDate->copy()->addDay();

            $query = $modelClass::query()
                ->whereHas('passwordSecurity', function ($q) use ($targetDate, $nextDate) {
                    $q->where('is_active', true)
                        ->whereNotNull('password_expires_at')
                        ->whereBetween('password_expires_at', [$targetDate, $nextDate])
                        ->where('password_must_change', false);
                });

            $targets = $targets->merge($query->get());
        }

        return $targets->unique('id');
    }

    /**
     * 만료된 사용자 찾기
     */
    protected function findExpiredUsers(string $modelClass)
    {
        $gracePeriodDays = config('password-security.expiration.grace_period_days', 0);
        $cutoffDate = now()->subDays($gracePeriodDays);

        return $modelClass::query()
            ->whereHas('passwordSecurity', function ($q) use ($cutoffDate) {
                $q->where('is_active', true)
                    ->whereNotNull('password_expires_at')
                    ->where('password_expires_at', '<', $cutoffDate)
                    ->where('password_must_change', false);
            })
            ->get();
    }

    /**
     * 만료 예정 사용자에게 알림
     */
    protected function notifyExpiringUser($user): void
    {
        $daysRemaining = method_exists($user, 'getDaysUntilPasswordExpiration') 
            ? $user->getDaysUntilPasswordExpiration() 
            : 0;

        $user->notify(new PasswordExpirationNotification($daysRemaining, false));
        $this->line("  만료 예정 알림: {$user->email} (D-{$daysRemaining})");
    }

    /**
     * 만료된 사용자에게 알림
     */
    protected function notifyExpiredUser($user): void
    {
        $user->notify(new PasswordExpirationNotification(0, true));
        $this->line("  만료 알림: {$user->email}");
    }
}

