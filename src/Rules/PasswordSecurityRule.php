<?php

namespace CmsOrbit\PasswordSecurity\Rules;

use Closure;
use CmsOrbit\PasswordSecurity\Validators\PasswordValidator;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nova 및 일반 폼에서 사용 가능한 패스워드 보안 검증 Rule
 * 
 * 사용법:
 * Password::make('Password', 'password')
 *     ->rules('required', new PasswordSecurityRule($this->resource))
 */
class PasswordSecurityRule implements ValidationRule
{
    protected $model;
    protected PasswordValidator $validator;

    /**
     * @param mixed $model Nova Resource의 $this->resource 또는 모델 인스턴스
     */
    public function __construct($model = null)
    {
        $this->model = $model;
        $this->validator = new PasswordValidator();
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!config('password-security.enabled')) {
            return;
        }

        // 빈 값은 nullable/required 룰에서 처리
        if (empty($value)) {
            return;
        }

        try {
            // 평문 패스워드로 전체 검증 실행
            $this->validator->validate($value, $this->model, true);
        } catch (\Exception $e) {
            $fail($e->getMessage());
        }
    }
}

