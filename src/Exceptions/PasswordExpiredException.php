<?php

namespace CmsOrbit\PasswordSecurity\Exceptions;

use Exception;

class PasswordExpiredException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        if (empty($message)) {
            $message = trans('password-security::messages.password_expired');
        }
        
        parent::__construct($message, $code, $previous);
    }
}

