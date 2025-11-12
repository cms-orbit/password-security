<?php

namespace CmsOrbit\PasswordSecurity\Traits;

use Carbon\Carbon;
use CmsOrbit\PasswordSecurity\Models\PasswordHistory;
use CmsOrbit\PasswordSecurity\Models\PasswordSecurity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Hash;

trait HasPasswordSecurity
{
    /**
     * Boot the trait.
     */
    public static function bootHasPasswordSecurity(): void
    {
        // 모델이 생성된 직후 보안 레코드 생성
        static::created(function ($model) {
            $model->createPasswordSecurity();
        });

        // 패스워드가 변경될 때 처리
        static::updating(function ($model) {
            $passwordField = $model->getPasswordFieldName();
            
            if ($model->isDirty($passwordField)) {
                $security = $model->passwordSecurity;
                if ($security) {
                    $security->password_changed_at = now();
                    $security->password_must_change = false;
                    
                    // 만료일 재계산
                    if (config('password-security.expiration.enabled')) {
                        $expiresInDays = config('password-security.expiration.expires_in_days', 90);
                        $security->password_expires_at = now()->addDays($expiresInDays);
                    }
                    
                    $security->save();
                }
            }
        });
    }

    /**
     * 패스워드 보안 레코드 관계 (1:1)
     */
    public function passwordSecurity(): MorphOne
    {
        return $this->morphOne(PasswordSecurity::class, 'securable');
    }

    /**
     * 패스워드 히스토리 관계
     */
    public function passwordHistories(): MorphMany
    {
        return $this->morphMany(PasswordHistory::class, 'securable');
    }

    /**
     * 패스워드 보안 레코드 생성
     */
    public function createPasswordSecurity(): PasswordSecurity
    {
        $expiresInDays = config('password-security.expiration.expires_in_days', 90);
        
        return $this->passwordSecurity()->create([
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays($expiresInDays),
            'password_must_change' => false,
            'is_active' => true,
        ]);
    }

    /**
     * 패스워드 필드명 가져오기
     * 모델에서 오버라이드 가능
     */
    public function getPasswordFieldName(): string
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'passwordSecurityField')) {
            return $this->passwordSecurityField;
        }
        
        // 2. 메서드로 지정
        if (method_exists($this, 'passwordSecurityFieldName')) {
            return $this->passwordSecurityFieldName();
        }
        
        // 3. 기본값
        return 'password';
    }

    /**
     * 개인정보 필드 목록 가져오기
     * 모델에서 오버라이드 가능
     */
    public function getPersonalInfoFields(): array
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'passwordSecurityPersonalFields')) {
            return $this->passwordSecurityPersonalFields;
        }
        
        // 2. 메서드로 지정
        if (method_exists($this, 'passwordSecurityPersonalFields')) {
            return $this->passwordSecurityPersonalFields();
        }
        
        // 3. 기본값
        return [
            'name' => 'name',
            'email' => 'email',
        ];
    }

    /**
     * 패스워드가 만료되었는지 확인
     */
    public function isPasswordExpired(): bool
    {
        if (!config('password-security.expiration.enabled')) {
            return false;
        }

        $security = $this->passwordSecurity;
        if (!$security) {
            return false;
        }

        if ($security->password_must_change) {
            return true;
        }

        if (!$security->password_expires_at) {
            return false;
        }

        $gracePeriodDays = config('password-security.expiration.grace_period_days', 0);
        $expirationDate = Carbon::parse($security->password_expires_at)->addDays($gracePeriodDays);

        return now()->greaterThan($expirationDate);
    }

    /**
     * 패스워드 만료까지 남은 일수
     */
    public function getDaysUntilPasswordExpiration(): ?int
    {
        if (!config('password-security.expiration.enabled')) {
            return null;
        }

        $security = $this->passwordSecurity;
        if (!$security || !$security->password_expires_at) {
            return null;
        }

        $expirationDate = Carbon::parse($security->password_expires_at);
        $daysRemaining = now()->diffInDays($expirationDate, false);

        return $daysRemaining > 0 ? (int) $daysRemaining : 0;
    }

    /**
     * 패스워드 히스토리에 추가
     */
    public function addPasswordToHistory(string $passwordHash, ?int $changedBy = null): void
    {
        if (!config('password-security.history.enabled')) {
            return;
        }

        $this->passwordHistories()->create([
            'password_hash' => $passwordHash,
            'changed_at' => now(),
            'changed_by' => $changedBy,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        // 오래된 히스토리 정리
        $this->cleanupOldPasswordHistories();
    }

    /**
     * 오래된 패스워드 히스토리 정리
     */
    protected function cleanupOldPasswordHistories(): void
    {
        if (!config('password-security.history.auto_cleanup', true)) {
            return;
        }

        $keepDays = config('password-security.history.keep_history_for_days', 365);
        $cutoffDate = now()->subDays($keepDays);

        $this->passwordHistories()
            ->where('changed_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * 최근 N개의 패스워드와 일치하는지 확인
     */
    public function isPasswordInHistory(string $password): bool
    {
        if (!config('password-security.history.enabled')) {
            return false;
        }

        $checkLastN = config('password-security.history.check_last_n_passwords', 3);
        
        $recentPasswords = $this->passwordHistories()
            ->orderByDesc('changed_at')
            ->limit($checkLastN)
            ->get();

        foreach ($recentPasswords as $history) {
            if (Hash::check($password, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 계정이 활성화되어 있는지 확인
     */
    public function isAccountActive(): bool
    {
        if (!config('password-security.inactive_accounts.enabled')) {
            return true;
        }

        $security = $this->passwordSecurity;
        return $security ? $security->is_active : true;
    }

    /**
     * 계정 비활성화
     */
    public function deactivateAccount(string $reason = 'inactivity'): void
    {
        $security = $this->passwordSecurity;
        if ($security) {
            $security->is_active = false;
            $security->deactivated_at = now();
            $security->deactivation_reason = $reason;
            $security->save();
        }
    }

    /**
     * 계정 활성화
     */
    public function activateAccount(): void
    {
        $security = $this->passwordSecurity;
        if ($security) {
            $security->is_active = true;
            $security->deactivated_at = null;
            $security->deactivation_reason = null;
            $security->save();
        }
    }

    /**
     * 마지막 로그인 일시 업데이트
     */
    public function updateLastLogin(): void
    {
        $security = $this->passwordSecurity;
        if ($security) {
            $security->last_login_at = now();
            $security->save();
        }
    }

    /**
     * 비활성화까지 남은 일수
     */
    public function getDaysUntilDeactivation(): ?int
    {
        if (!config('password-security.inactive_accounts.enabled')) {
            return null;
        }

        $security = $this->passwordSecurity;
        if (!$security) {
            return null;
        }

        $lastLoginAt = $security->last_login_at ?? $this->created_at;
        $inactiveDays = config('password-security.inactive_accounts.inactive_days', 90);
        $deactivationDate = Carbon::parse($lastLoginAt)->addDays($inactiveDays);

        $daysRemaining = now()->diffInDays($deactivationDate, false);

        return $daysRemaining > 0 ? (int) $daysRemaining : 0;
    }

    /**
     * 계정이 비활성화 대상인지 확인
     */
    public function shouldBeDeactivated(): bool
    {
        if (!config('password-security.inactive_accounts.enabled')) {
            return false;
        }

        if (!$this->isAccountActive()) {
            return false;
        }

        // 제외 조건 확인
        $exclusions = config('password-security.inactive_accounts.exclusions', []);
        
        // 역할 제외
        if (!empty($exclusions['roles']) && method_exists($this, 'hasRole')) {
            foreach ($exclusions['roles'] as $role) {
                if ($this->hasRole($role)) {
                    return false;
                }
            }
        }

        // 이메일 제외
        if (!empty($exclusions['emails']) && in_array($this->email, $exclusions['emails'])) {
            return false;
        }

        // 비활성 기간 확인
        $security = $this->passwordSecurity;
        if (!$security) {
            return false;
        }

        $lastLoginAt = $security->last_login_at ?? $this->created_at;
        $inactiveDays = config('password-security.inactive_accounts.inactive_days', 90);
        $inactiveDate = Carbon::parse($lastLoginAt)->addDays($inactiveDays);

        return now()->greaterThanOrEqualTo($inactiveDate);
    }
}

