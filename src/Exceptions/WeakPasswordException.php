<?php

namespace CmsOrbit\PasswordSecurity\Exceptions;

use Exception;

class WeakPasswordException extends Exception
{
    protected array $errors;

    public function __construct(string $message = "", array $errors = [], int $code = 0, ?Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 에러 목록 가져오기
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 첫 번째 에러 메시지 가져오기
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * 모든 에러 메시지를 문자열로 가져오기
     */
    public function getAllErrorMessages(): string
    {
        return implode(' ', $this->errors);
    }
}

