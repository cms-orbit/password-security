<?php

namespace CmsOrbit\PasswordSecurity\Validators\Rules;

class HistoryRule
{
    /**
     * 패스워드 히스토리 검증
     */
    public function validate(string $password, $model, array $config = []): bool
    {
        if (!($config['enabled'] ?? true)) {
            return true;
        }

        // 모델이 HasPasswordSecurity Trait을 사용하는지 확인
        if (!method_exists($model, 'isPasswordInHistory')) {
            return true;
        }

        // 신규 생성 시에는 히스토리 체크하지 않음
        if (!$model->exists) {
            return true;
        }

        // 히스토리에 있는지 확인
        return !$model->isPasswordInHistory($password);
    }

    /**
     * 에러 메시지 가져오기
     */
    public function getMessage(array $config = []): string
    {
        $count = $config['check_last_n_passwords'] ?? 3;
        
        return trans('password-security::validation.password_in_history', ['count' => $count]);
    }
}

