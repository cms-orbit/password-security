<?php

namespace CmsOrbit\PasswordSecurity\Validators;

use CmsOrbit\PasswordSecurity\Exceptions\WeakPasswordException;
use CmsOrbit\PasswordSecurity\Validators\Rules\CommonPatternRule;
use CmsOrbit\PasswordSecurity\Validators\Rules\ComplexityRule;
use CmsOrbit\PasswordSecurity\Validators\Rules\HistoryRule;
use CmsOrbit\PasswordSecurity\Validators\Rules\PersonalInfoRule;
use Illuminate\Support\Facades\Log;

class PasswordValidator
{
    protected ComplexityRule $complexityRule;
    protected CommonPatternRule $commonPatternRule;
    protected PersonalInfoRule $personalInfoRule;
    protected HistoryRule $historyRule;

    protected array $errors = [];

    public function __construct()
    {
        $this->complexityRule = new ComplexityRule();
        $this->commonPatternRule = new CommonPatternRule();
        $this->personalInfoRule = new PersonalInfoRule();
        $this->historyRule = new HistoryRule();
    }

    /**
     * 패스워드 검증
     *
     * @param string $password 검증할 패스워드
     * @param mixed $model 사용자 모델
     * @param bool $throwException 예외 발생 여부
     * @return bool
     * @throws WeakPasswordException
     */
    public function validate(string $password, $model = null, bool $throwException = null): bool
    {
        if (!config('password-security.enabled')) {
            return true;
        }

        $throwException = $throwException ?? config('password-security.exceptions.throw_exceptions', true);
        $this->errors = [];

        // 1. 복잡도 검증
        $complexityConfig = config('password-security.complexity', []);
        if (!$this->complexityRule->validate($password, $complexityConfig)) {
            $this->addError('complexity', $this->complexityRule->getDetailedMessage($password, $complexityConfig));
        }

        // 2. 일반 패턴 검증
        $commonPatternConfig = config('password-security.common_patterns', []);
        if (!$this->commonPatternRule->validate($password, $commonPatternConfig)) {
            $this->addError('common_pattern', $this->commonPatternRule->getMessage($commonPatternConfig));
        }

        // 3. 개인정보 검증 (모델이 있는 경우)
        if ($model) {
            $personalInfoConfig = config('password-security.personal_info', []);
            if (!$this->personalInfoRule->validate($password, $model, $personalInfoConfig)) {
                $this->addError('personal_info', $this->personalInfoRule->getMessage($personalInfoConfig));
            }

            // 4. 히스토리 검증
            $historyConfig = config('password-security.history', []);
            if (!$this->historyRule->validate($password, $model, $historyConfig)) {
                $this->addError('history', $this->historyRule->getMessage($historyConfig));
            }
        }

        // 로깅
        if (!empty($this->errors) && config('password-security.exceptions.log_violations', true)) {
            $this->logViolation($model);
        }

        // 검증 결과 처리
        if (!empty($this->errors)) {
            if ($throwException) {
                throw new WeakPasswordException($this->getErrorMessage(), $this->errors);
            }
            return false;
        }

        return true;
    }

    /**
     * 에러 추가
     */
    protected function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /**
     * 에러 메시지 가져오기
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 첫 번째 에러 메시지 가져오기
     */
    public function getErrorMessage(): string
    {
        if (empty($this->errors)) {
            return trans('password-security::validation.validation_failed');
        }

        return reset($this->errors);
    }

    /**
     * 모든 에러 메시지 문자열로 가져오기
     */
    public function getAllErrorMessages(): string
    {
        return implode(' ', $this->errors);
    }

    /**
     * 위반 사항 로깅
     */
    protected function logViolation($model): void
    {
        $channel = config('password-security.exceptions.log_channel');
        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

        $context = [
            'model' => $model ? get_class($model) : 'unknown',
            'model_id' => $model?->id ?? 'unknown',
            'errors' => array_keys($this->errors),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        $logger->warning('Password security validation failed', $context);
    }

    /**
     * 복잡도만 검증
     */
    public function validateComplexity(string $password): bool
    {
        $complexityConfig = config('password-security.complexity', []);
        return $this->complexityRule->validate($password, $complexityConfig);
    }

    /**
     * 일반 패턴만 검증
     */
    public function validateCommonPattern(string $password): bool
    {
        $commonPatternConfig = config('password-security.common_patterns', []);
        return $this->commonPatternRule->validate($password, $commonPatternConfig);
    }

    /**
     * 개인정보만 검증
     */
    public function validatePersonalInfo(string $password, $model): bool
    {
        $personalInfoConfig = config('password-security.personal_info', []);
        return $this->personalInfoRule->validate($password, $model, $personalInfoConfig);
    }

    /**
     * 히스토리만 검증
     */
    public function validateHistory(string $password, $model): bool
    {
        $historyConfig = config('password-security.history', []);
        return $this->historyRule->validate($password, $model, $historyConfig);
    }
}

