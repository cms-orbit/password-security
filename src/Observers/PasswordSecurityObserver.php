<?php

namespace CmsOrbit\PasswordSecurity\Observers;

use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordSecurityObserver
{
    protected PasswordValidator $validator;

    public function __construct()
    {
        $this->validator = new PasswordValidator();
    }

    /**
     * 모델 저장 전 처리
     */
    public function saving($model): void
    {
        if (!config('password-security.enabled')) {
            return;
        }

        $passwordField = $this->getPasswordField($model);

        // 패스워드 필드가 변경되었는지 확인
        if (!$model->isDirty($passwordField)) {
            return;
        }

        $newPassword = $model->{$passwordField};

        // 이미 해시된 값인지 확인 (bcrypt, argon2 등)
        if ($this->isAlreadyHashed($newPassword)) {
            return;
        }

        // 패스워드 검증
        $this->validator->validate($newPassword, $model);

        // 검증 통과 후 해시 처리는 모델의 mutator에 맡김
        // 단, mutator가 없으면 여기서 해시 처리
        if (!method_exists($model, 'set' . Str::studly($passwordField) . 'Attribute')) {
            $model->{$passwordField} = Hash::make($newPassword);
        }
    }

    /**
     * 모델 생성 후 처리
     */
    public function created($model): void
    {
        $this->savePasswordHistory($model, true);
    }

    /**
     * 모델 업데이트 후 처리
     */
    public function updated($model): void
    {
        $this->savePasswordHistory($model, false);
    }

    /**
     * 패스워드 히스토리 저장
     */
    protected function savePasswordHistory($model, bool $isNewRecord): void
    {
        if (!config('password-security.enabled')) {
            return;
        }

        if (!config('password-security.history.enabled')) {
            return;
        }

        $passwordField = $this->getPasswordField($model);

        // 신규 생성이 아니면 패스워드가 변경되었는지 확인
        if (!$isNewRecord && !$model->wasChanged($passwordField)) {
            return;
        }

        // 히스토리에 추가
        if (method_exists($model, 'addPasswordToHistory')) {
            $hashedPassword = $model->{$passwordField};
            $changedBy = auth()->id();
            
            $model->addPasswordToHistory($hashedPassword, $changedBy);
        }
    }

    /**
     * 패스워드 필드명 가져오기
     */
    protected function getPasswordField($model): string
    {
        if (method_exists($model, 'getPasswordFieldName')) {
            return $model->getPasswordFieldName();
        }

        return 'password';
    }

    /**
     * 이미 해시된 값인지 확인
     */
    protected function isAlreadyHashed(string $value): bool
    {
        // bcrypt
        if (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$') || str_starts_with($value, '$2b$')) {
            return true;
        }

        // argon2
        if (str_starts_with($value, '$argon2i$') || str_starts_with($value, '$argon2id$')) {
            return true;
        }

        // 일반적으로 해시는 60자 이상
        if (strlen($value) >= 60) {
            return true;
        }

        return false;
    }
}

