<?php

namespace CmsOrbit\PasswordSecurity\Commands;

use CmsOrbit\PasswordSecurity\Notifications\AccountDeactivatedNotification;
use CmsOrbit\PasswordSecurity\Traits\HasPasswordSecurity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeactivateInactiveAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-security:deactivate-inactive 
                            {--dry-run : 실제 비활성화 없이 대상만 확인}
                            {--notify : 비활성화 전 알림 발송}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '일정 기간 미사용 계정을 비활성화합니다';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('password-security.enabled') || !config('password-security.inactive_accounts.enabled')) {
            $this->info('휴면 계정 관리가 비활성화되어 있습니다.');
            return self::SUCCESS;
        }

        $this->info('휴면 계정 검색 중...');

        $models = config('password-security.models', []);
        $totalDeactivated = 0;
        $totalNotified = 0;

        foreach ($models as $modelClass => $settings) {
            if (!class_exists($modelClass)) {
                $this->warn("모델을 찾을 수 없습니다: {$modelClass}");
                continue;
            }

            $this->line("처리 중: {$modelClass}");

            // 비활성화 대상 찾기
            $deactivateTargets = $this->findDeactivateTargets($modelClass);
            $notifyTargets = $this->findNotifyTargets($modelClass);

            if ($this->option('dry-run')) {
                $this->info("  비활성화 대상: {$deactivateTargets->count()}명");
                $this->info("  알림 대상: {$notifyTargets->count()}명");
                continue;
            }

            // 비활성화 처리
            foreach ($deactivateTargets as $user) {
                $this->deactivateUser($user);
                $totalDeactivated++;
            }

            // 알림 발송
            if ($this->option('notify') && config('password-security.notifications.enabled')) {
                foreach ($notifyTargets as $user) {
                    $this->notifyUser($user);
                    $totalNotified++;
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run 모드: 실제 변경사항 없음');
        } else {
            $this->info("완료: {$totalDeactivated}명 비활성화, {$totalNotified}명 알림 발송");
        }

        return self::SUCCESS;
    }

    /**
     * 비활성화 대상 찾기
     */
    protected function findDeactivateTargets(string $modelClass)
    {
        $inactiveDays = config('password-security.inactive_accounts.inactive_days', 90);
        $cutoffDate = now()->subDays($inactiveDays);
        
        $query = $modelClass::query()
            ->whereHas('passwordSecurity', function ($q) use ($cutoffDate) {
                $q->where('is_active', true)
                    ->where(function ($subQ) use ($cutoffDate) {
                        $subQ->where(function ($innerQ) use ($cutoffDate) {
                            $innerQ->whereNotNull('last_login_at')
                                ->where('last_login_at', '<', $cutoffDate);
                        })
                        ->orWhere(function ($innerQ) use ($cutoffDate) {
                            $innerQ->whereNull('last_login_at')
                                ->where('created_at', '<', $cutoffDate);
                        });
                    });
            });

        // 제외 조건 적용
        $this->applyExclusions($query, $modelClass);

        return $query->get();
    }

    /**
     * 알림 대상 찾기
     */
    protected function findNotifyTargets(string $modelClass)
    {
        $inactiveDays = config('password-security.inactive_accounts.inactive_days', 90);
        $notifyBeforeDays = config('password-security.inactive_accounts.notify_before_days', [14, 7, 3]);
        
        $targets = collect();

        foreach ($notifyBeforeDays as $days) {
            $targetDate = now()->subDays($inactiveDays - $days)->startOfDay();
            $nextDate = $targetDate->copy()->addDay();

            $query = $modelClass::query()
                ->whereHas('passwordSecurity', function ($q) use ($targetDate, $nextDate) {
                    $q->where('is_active', true)
                        ->where(function ($subQ) use ($targetDate, $nextDate) {
                            $subQ->where(function ($innerQ) use ($targetDate, $nextDate) {
                                $innerQ->whereNotNull('last_login_at')
                                    ->whereBetween('last_login_at', [$targetDate, $nextDate]);
                            })
                            ->orWhere(function ($innerQ) use ($targetDate, $nextDate) {
                                $innerQ->whereNull('last_login_at')
                                    ->whereBetween('created_at', [$targetDate, $nextDate]);
                            });
                        });
                });

            $this->applyExclusions($query, $modelClass);

            $targets = $targets->merge($query->get());
        }

        return $targets->unique('id');
    }

    /**
     * 제외 조건 적용
     */
    protected function applyExclusions($query, string $modelClass): void
    {
        $exclusions = config('password-security.inactive_accounts.exclusions', []);

        // 역할 제외
        if (!empty($exclusions['roles'])) {
            $model = new $modelClass;
            if (method_exists($model, 'hasRole')) {
                $query->whereDoesntHave('roles', function ($q) use ($exclusions) {
                    $q->whereIn('name', $exclusions['roles']);
                });
            }
        }

        // 이메일 제외
        if (!empty($exclusions['emails'])) {
            $query->whereNotIn('email', $exclusions['emails']);
        }
    }

    /**
     * 사용자 비활성화
     */
    protected function deactivateUser($user): void
    {
        if (method_exists($user, 'deactivateAccount')) {
            $user->deactivateAccount('inactivity');
            $this->line("  비활성화: {$user->email}");
        } else {
            $activeField = config('password-security.inactive_accounts.active_field', 'is_active');
            $user->{$activeField} = false;
            $user->save();
            $this->line("  비활성화: {$user->email}");
        }

        // 비활성화 알림 발송
        if (config('password-security.notifications.enabled') && 
            config('password-security.notifications.inactive_account.enabled')) {
            $inactiveDays = config('password-security.inactive_accounts.inactive_days', 90);
            $user->notify(new AccountDeactivatedNotification(0, true, $inactiveDays));
        }
    }

    /**
     * 사용자에게 알림 발송
     */
    protected function notifyUser($user): void
    {
        $daysRemaining = method_exists($user, 'getDaysUntilDeactivation') 
            ? $user->getDaysUntilDeactivation() 
            : 0;

        $inactiveDays = config('password-security.inactive_accounts.inactive_days', 90);
        
        $user->notify(new AccountDeactivatedNotification($daysRemaining, false, $inactiveDays));
        $this->line("  알림 발송: {$user->email} (D-{$daysRemaining})");
    }
}

