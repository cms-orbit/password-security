<?php

namespace CmsOrbit\PasswordSecurity\Exceptions;

use Exception;

class PasswordReusedException extends Exception
{
    protected int $historyCount;

    public function __construct(int $historyCount = 3, int $code = 0, ?Exception $previous = null)
    {
        $this->historyCount = $historyCount;
        $message = trans('password-security::validation.password_in_history', ['count' => $historyCount]);
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * 체크한 히스토리 개수 가져오기
     */
    public function getHistoryCount(): int
    {
        return $this->historyCount;
    }
}

