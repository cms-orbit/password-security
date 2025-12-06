<?php

namespace CmsOrbit\PasswordSecurity\Commands;

use CmsOrbit\PasswordSecurity\Notifications\AccountDeactivatedNotification;
use CmsOrbit\PasswordSecurity\Traits\HasFreezePolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeactivateInactiveAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-security:freeze
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

        $targetModels = config('password-security.inactive_accounts.target_models', []);
        $targetTenantModels = config('password-security.inactive_accounts.target_tenant_models', []);

        if (empty($targetModels) && empty($targetTenantModels)) {
            $this->warn('처리할 모델이 설정되어 있지 않습니다.');
            return self::SUCCESS;
        }

        $totalDeactivated = 0;
        $totalNotified = 0;
        $totalDeleted = 0;
        $totalForceDeleted = 0;

        // Central 모델 처리
        if (!empty($targetModels)) {
            $this->info('=== Central 모델 처리 시작 ===');
            foreach ($targetModels as $modelClass) {
                if (!class_exists($modelClass)) {
                    $this->warn("모델을 찾을 수 없습니다: {$modelClass}");
                    continue;
                }

                $this->line("처리 중: {$modelClass}");
                [$deactivated, $notified, $deleted, $forceDeleted] = $this->processModel($modelClass);
                $totalDeactivated += $deactivated;
                $totalNotified += $notified;
                $totalDeleted += $deleted;
                $totalForceDeleted += $forceDeleted;
            }
        }

        // Tenant 모델 처리
        if (!empty($targetTenantModels)) {
            $this->info('=== Tenant 모델 처리 시작 ===');

            // Tenant 모델이 있는지 확인
            $tenantModelClass = config('tenancy.tenant_model');
            if (!class_exists($tenantModelClass)) {
                $this->warn("Tenant 모델을 찾을 수 없습니다: {$tenantModelClass}");
            } else {
                $tenants = $tenantModelClass::all();
                $this->info("총 {$tenants->count()}개의 tenant를 처리합니다.");

                foreach ($tenants as $tenant) {
                    $this->line("Tenant 처리 중: {$tenant->id}");

                    // Tenant 컨텍스트 초기화
                    if (function_exists('tenancy')) {
                        tenancy()->initialize($tenant);
                    }

                    try {
                        foreach ($targetTenantModels as $modelClass) {
                            if (!class_exists($modelClass)) {
                                $this->warn("모델을 찾을 수 없습니다: {$modelClass}");
                                continue;
                            }

                            $this->line("  처리 중: {$modelClass}");
                            [$deactivated, $notified, $deleted, $forceDeleted] = $this->processModel($modelClass);
                            $totalDeactivated += $deactivated;
                            $totalNotified += $notified;
                            $totalDeleted += $deleted;
                            $totalForceDeleted += $forceDeleted;
                        }
                    } finally {
                        // Tenant 컨텍스트 종료
                        if (function_exists('tenancy')) {
                            tenancy()->end();
                        }
                    }
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run 모드: 실제 변경사항 없음');
        } else {
            $this->info("완료: {$totalDeactivated}명 비활성화, {$totalNotified}명 알림 발송, {$totalDeleted}명 삭제, {$totalForceDeleted}명 강제삭제");
        }

        return self::SUCCESS;
    }

    /**
     * 모델 처리
     *
     * @param string $modelClass
     * @return array [deactivated count, notified count, deleted count, forceDeleted count]
     */
    protected function processModel(string $modelClass): array
    {
        $deactivated = 0;
        $notified = 0;
        $deleted = 0;
        $forceDeleted = 0;

        $model = new $modelClass;

        // HasFreezePolicy trait를 사용하는지 확인
        if (!in_array(HasFreezePolicy::class, class_uses_recursive($model))) {
            $this->warn("  모델이 HasFreezePolicy trait를 사용하지 않습니다: {$modelClass}");
            return [0, 0, 0, 0];
        }

        // 강제삭제 대상 찾기 및 처리 (우선 처리)
        $forceDeleteTargets = $this->findForceDeleteTargets($modelClass);
        if (!$this->option('dry-run')) {
            foreach ($forceDeleteTargets as $user) {
                $this->forceDeleteUser($user);
                $forceDeleted++;
            }
        }

        // 모든 레코드를 chunk로 처리
        $statusField = $model->getFreezeStatusFieldName();
        $totalCount = $modelClass::query()->count();
        $chunkSize = 100;
        $totalChunks = ceil($totalCount / $chunkSize);

        $this->info("  총 {$totalCount}개 레코드를 {$totalChunks}개 chunk로 처리합니다.");

        $allDeactivateTargets = collect();
        $allNotifyTargets = collect();
        $allDeleteTargets = collect();

        $page = 0;
        $query = $modelClass::query()->orderBy('id');
        $this->applyExclusions($query, $modelClass);

        $query->chunk($chunkSize, function ($users) use ($modelClass, &$page, $totalChunks, &$allDeactivateTargets, &$allNotifyTargets, &$allDeleteTargets) {
            $page++;
            $this->line("  [Chunk {$page}/{$totalChunks}] 처리 중...");

            $tableData = [];
            foreach ($users as $user) {
                $analysis = $this->analyzeUser($user, $modelClass);
                $tableData[] = $analysis['row'];

                // 대상 수집
                if ($analysis['isDeactivateTarget']) {
                    $allDeactivateTargets->push($user);
                }
                if ($analysis['isNotifyTarget']) {
                    $allNotifyTargets->push($user);
                }
                if ($analysis['isDeleteTarget']) {
                    $allDeleteTargets->push($user);
                }
            }

            // 테이블 표시
            if (!empty($tableData)) {
                $this->table([
                    'ID',
                    'Email/Name',
                    '기준일',
                    '휴면기준일',
                    '활성화 유지일',
                    '비활성화',
                    '삭제',
                    '알림',
                    '강제삭제',
                    '제외'
                ], $tableData);
            }
        });

        if ($this->option('dry-run')) {
            $this->info("  강제삭제 대상: {$forceDeleteTargets->count()}명");
            $this->info("  삭제 대상: {$allDeleteTargets->count()}명");
            $this->info("  비활성화 대상: {$allDeactivateTargets->count()}명");
            $this->info("  알림 대상: {$allNotifyTargets->count()}명");
            return [0, 0, 0, 0];
        }

        // 삭제 처리
        foreach ($allDeleteTargets as $user) {
            $this->deleteUser($user);
            $deleted++;
        }

        // 비활성화 처리
        foreach ($allDeactivateTargets as $user) {
            $this->deactivateUser($user);
            $deactivated++;
        }

        // 알림 발송
        if ($this->option('notify') && config('password-security.notifications.enabled')) {
            foreach ($allNotifyTargets as $user) {
                $this->notifyUser($user);
                $notified++;
            }
        }

        return [$deactivated, $notified, $deleted, $forceDeleted];
    }

    /**
     * 사용자 분석
     *
     * @param mixed $user
     * @param string $modelClass
     * @return array
     */
    protected function analyzeUser($user, string $modelClass): array
    {
        $identifier = $user->email ?? $user->name ?? $user->id ?? 'Unknown';
        $baseDate = null;
        $inactiveDays = 90;
        $freezeDate = null;
        $daysRemaining = null;
        $isDeactivateTarget = false;
        $isNotifyTarget = false;
        $isDeleteTarget = false;
        $isForceDeleteTarget = false;
        $isExclusion = false;

        if (method_exists($user, 'isExclusion') && $user->isExclusion()) {
            $isExclusion = true;
        }

        if (method_exists($user, 'getDaysUntilFreeze')) {
            $baseDate = $user->getDaysUntilFreeze();
            $inactiveDays = method_exists($user, 'getFreezeInactiveDays')
                ? $user->getFreezeInactiveDays()
                : 90;
            $freezeDate = $baseDate->copy()->addDays($inactiveDays);
            $daysRemaining = now()->diffInDays($freezeDate, false);

            // 비활성화 대상 확인
            if ($daysRemaining <= 0) {
                $statusField = method_exists($user, 'getFreezeStatusFieldName')
                    ? $user->getFreezeStatusFieldName()
                    : 'is_freeze';
                if (!$user->{$statusField}) {
                    $isDeactivateTarget = true;
                }
            }

            // 알림 대상 확인
            if ($daysRemaining > 0) {
                $notifyBeforeDays = method_exists($user, 'getFreezeNotifyBeforeDays')
                    ? $user->getFreezeNotifyBeforeDays()
                    : [14, 7, 3];
                if (in_array($daysRemaining, $notifyBeforeDays)) {
                    $statusField = method_exists($user, 'getFreezeStatusFieldName')
                        ? $user->getFreezeStatusFieldName()
                        : 'is_freeze';
                    if (!$user->{$statusField}) {
                        $isNotifyTarget = true;
                    }
                }
            }

            // 삭제 대상 확인
            $statusField = method_exists($user, 'getFreezeStatusFieldName')
                ? $user->getFreezeStatusFieldName()
                : 'is_freeze';
            if ($user->{$statusField}) {
                $deleteAfterDays = method_exists($user, 'getFreezeDeleteAfterDays')
                    ? $user->getFreezeDeleteAfterDays()
                    : 7;
                if ($deleteAfterDays !== null) {
                    $deleteDate = $baseDate->copy()->addDays($inactiveDays)->addDays($deleteAfterDays);
                    $deleteDaysDiff = now()->diffInDays($deleteDate, false);
                    if ($deleteDaysDiff <= 0) {
                        $isDeleteTarget = true;
                    }
                }
            }
        }

        // 강제삭제는 findForceDeleteTargets에서 별도 처리하므로 여기서는 확인하지 않음

        return [
            'row' => [
                substr($user->id ?? 'N/A', 0, 8),
                substr($identifier, 0, 20),
                $baseDate ? $baseDate->format('Y-m-d') : 'N/A',
                $inactiveDays . '일',
                $daysRemaining !== null ? ($daysRemaining > 0 ? "{$daysRemaining}일" : "만료") : 'N/A',
                $isDeactivateTarget ? '✓' : '-',
                $isDeleteTarget ? '✓' : '-',
                $isNotifyTarget ? '✓' : '-',
                '-', // 강제삭제는 별도 처리
                $isExclusion ? '✓' : '-',
            ],
            'isDeactivateTarget' => $isDeactivateTarget,
            'isNotifyTarget' => $isNotifyTarget,
            'isDeleteTarget' => $isDeleteTarget,
        ];
    }

    /**
     * 비활성화 대상 찾기
     * 기준일 + inactiveDays <= 현재 && is_freeze == false
     * (이제 processModel에서 analyzeUser로 처리하므로 사용 안 함)
     */
    protected function findDeactivateTargets(string $modelClass)
    {
        return collect();
    }

    /**
     * 알림 대상 찾기
     * 기준일 + inactiveDays - notifyBeforeDays 범위에 있는 계정
     * (이제 processModel에서 analyzeUser로 처리하므로 사용 안 함)
     */
    protected function findNotifyTargets(string $modelClass)
    {
        return collect();
    }

    /**
     * 제외 조건 적용
     */
    protected function applyExclusions($query, string $modelClass): void
    {
        $model = new $modelClass;

        // isExclusion 메서드가 있는 경우
        if (method_exists($model, 'isExclusion')) {
            // 각 레코드를 확인하여 제외
            // 이 방법은 비효율적이므로, 모델에서 쿼리 스코프를 제공하는 것이 좋습니다
            // 일단 기본 구현만 제공
        }
    }

    /**
     * 삭제 대상 찾기
     * is_freeze == true && 기준일 + inactiveDays + deleteAfterDays <= 현재
     * (이제 processModel에서 analyzeUser로 처리하므로 사용 안 함)
     */
    protected function findDeleteTargets(string $modelClass)
    {
        return collect();
    }

    /**
     * 강제삭제 대상 찾기 (SoftDeletes 사용 모델)
     * deleted_at + forceDeleteAfterDays <= 현재
     */
    protected function findForceDeleteTargets(string $modelClass)
    {
        $model = new $modelClass;

        // SoftDeletes trait를 사용하는지 확인
        $usesSoftDeletes = in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($model)
        );

        if (!$usesSoftDeletes) {
            return collect();
        }

        // HasFreezePolicy trait를 사용하는지 확인
        if (!in_array(HasFreezePolicy::class, class_uses_recursive($model))) {
            return collect();
        }

        $forceDeleteAfterDays = $model->getFreezeForceDeleteAfterDays();
        $cutoffDate = now()->subDays($forceDeleteAfterDays);

        // soft delete된 레코드만 조회
        $query = $modelClass::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate);

        // 제외 조건 적용
        $this->applyExclusions($query, $modelClass);

        $targets = collect();
        $query->chunk(100, function ($users) use (&$targets) {
            foreach ($users as $user) {
                if (method_exists($user, 'isExclusion') && $user->isExclusion()) {
                    continue;
                }

                $targets->push($user);
            }
        });

        return $targets;
    }

    /**
     * 사용자 비활성화
     */
    protected function deactivateUser($user): void
    {
        // HasFreezePolicy trait의 freeze 메서드 사용
        if (method_exists($user, 'freeze')) {
            $user->freeze();
            $email = $user->email ?? $user->id ?? 'Unknown';
            $this->line("  비활성화: {$email}");
        } else {
            $statusField = method_exists($user, 'getFreezeStatusFieldName')
                ? $user->getFreezeStatusFieldName()
                : 'is_freeze';
            $user->{$statusField} = true;
            $user->save();
            $email = $user->email ?? $user->id ?? 'Unknown';
            $this->line("  비활성화: {$email}");
        }

        // 비활성화 알림 발송
        if (config('password-security.notifications.enabled') &&
            config('password-security.notifications.inactive_account.enabled')) {
            $inactiveDays = method_exists($user, 'getFreezeInactiveDays')
                ? $user->getFreezeInactiveDays()
                : 90;
            $user->notify(new AccountDeactivatedNotification(0, true, $inactiveDays));
        }
    }

    /**
     * 사용자 삭제
     */
    protected function deleteUser($user): void
    {
        $email = $user->email ?? $user->id ?? 'Unknown';

        try {
            $user->delete();
            $this->line("  삭제: {$email}");
        } catch (\Exception $e) {
            $this->error("  삭제 실패: {$email} - {$e->getMessage()}");
        }
    }

    /**
     * 사용자 강제삭제 (SoftDeletes)
     */
    protected function forceDeleteUser($user): void
    {
        $email = $user->email ?? $user->id ?? 'Unknown';

        try {
            $user->forceDelete();
            $this->line("  강제삭제: {$email}");
        } catch (\Exception $e) {
            $this->error("  강제삭제 실패: {$email} - {$e->getMessage()}");
        }
    }

    /**
     * 사용자에게 알림 발송
     */
    protected function notifyUser($user): void
    {
        $daysRemaining = 0;

        // getDaysUntilFreeze 메서드 사용 (기준일 반환)
        if (method_exists($user, 'getDaysUntilFreeze')) {
            $baseDate = $user->getDaysUntilFreeze();
            $inactiveDays = method_exists($user, 'getFreezeInactiveDays')
                ? $user->getFreezeInactiveDays()
                : 90;

            // 기준일에 inactiveDays를 더해서 휴면 예정일 계산
            $freezeDate = $baseDate->copy()->addDays($inactiveDays);
            $daysRemaining = now()->diffInDays($freezeDate, false);
            $daysRemaining = max(0, $daysRemaining);
        } else {
            $inactiveDays = 90;
        }

        $user->notify(new AccountDeactivatedNotification($daysRemaining, false, $inactiveDays));
        $email = $user->email ?? $user->id ?? 'Unknown';
        $this->line("  알림 발송: {$email} (D-{$daysRemaining})");
    }
}

