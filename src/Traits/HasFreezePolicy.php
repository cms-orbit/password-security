<?php

namespace CmsOrbit\PasswordSecurity\Traits;

use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;

trait HasFreezePolicy
{
    /**
     * 휴면 기준일 가져오기 (기본값: 90일)
     * 모델에서 오버라이드 가능
     */
    public function getFreezeInactiveDays(): int
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'freezeInactiveDays')) {
            return $this->freezeInactiveDays;
        }

        // 2. 메서드로 지정
        if (method_exists($this, 'freezeInactiveDays')) {
            return $this->freezeInactiveDays();
        }

        // 3. 기본값
        return 90;
    }

    /**
     * 비활성화 전 알림 일자들 가져오기 (기본값: [14, 7, 3])
     * 모델에서 오버라이드 가능
     */
    public function getFreezeNotifyBeforeDays(): array
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'freezeNotifyBeforeDays')) {
            return $this->freezeNotifyBeforeDays;
        }

        // 2. 메서드로 지정
        if (method_exists($this, 'freezeNotifyBeforeDays')) {
            return $this->freezeNotifyBeforeDays();
        }

        // 3. 기본값
        return [14, 7, 3];
    }

    /**
     * 비활성화 후 삭제기간 가져오기 (기본값: 7일)
     * 모델에서 오버라이드 가능
     */
    public function getFreezeDeleteAfterDays(): int
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'freezeDeleteAfterDays')) {
            return $this->freezeDeleteAfterDays;
        }

        // 2. 메서드로 지정
        if (method_exists($this, 'freezeDeleteAfterDays')) {
            return $this->freezeDeleteAfterDays();
        }

        // 3. 기본값
        return 7;
    }

    /**
     * 강제삭제 기간 가져오기 (소프트삭제 모델의 경우, 기본값: 5일)
     * 모델에서 오버라이드 가능
     */
    public function getFreezeForceDeleteAfterDays(): int
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'freezeForceDeleteAfterDays')) {
            return $this->freezeForceDeleteAfterDays;
        }

        // 2. 메서드로 지정
        if (method_exists($this, 'freezeForceDeleteAfterDays')) {
            return $this->freezeForceDeleteAfterDays();
        }

        // 3. 기본값
        return 5;
    }

    /**
     * 계정활성화 상태 필드 이름 가져오기 (기본값: 'is_freeze')
     * 모델에서 오버라이드 가능
     */
    public function getFreezeStatusFieldName(): string
    {
        // 1. 프로퍼티로 지정
        if (property_exists($this, 'freezeStatusField')) {
            return $this->freezeStatusField;
        }

        // 2. 메서드로 지정
        if (method_exists($this, 'freezeStatusFieldName')) {
            return $this->freezeStatusFieldName();
        }

        // 3. 기본값
        return 'is_freeze';
    }

    /**
     * 휴면대상에서 제외할 모델인지 판별
     * 모델에서 오버라이드 가능
     *
     * @return bool true면 휴면대상에서 제외, false면 휴면대상에 포함
     */
    public function isExclusion(): bool
    {
        // 모델에서 오버라이드하여 구현
        return false;
    }

    /**
     * 활성화 상태가 며칠 남았는지 검사
     * 모델에서 오버라이드 가능
     *
     * @return \Carbon\Carbon 활성화 상태가 유지되는 날짜
     */
    public function getDaysUntilFreeze(): Carbon
    {
        // 모델에서 오버라이드하여 구현
        // 기본값: 30일 후
        return now()->addDays(30);
    }

    /**
     * 계정이 휴면 상태인지 확인
     *
     * @return bool
     */
    public function isFrozen(): bool
    {
        $statusField = $this->getFreezeStatusFieldName();

        if (!isset($this->attributes[$statusField])) {
            return false;
        }

        return (bool) $this->attributes[$statusField];
    }

    /**
     * 계정을 휴면 상태로 설정
     *
     * @return void
     */
    public function freeze(): void
    {
        $statusField = $this->getFreezeStatusFieldName();
        $this->{$statusField} = true;
        $this->save();
    }

    /**
     * 계정을 활성 상태로 복구
     *
     * @return void
     */
    public function unfreeze(): void
    {
        $statusField = $this->getFreezeStatusFieldName();
        $this->{$statusField} = false;
        $this->save();
    }
}
