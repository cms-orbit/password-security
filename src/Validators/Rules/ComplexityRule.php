<?php

namespace CmsOrbit\PasswordSecurity\Validators\Rules;

class ComplexityRule
{
    /**
     * 패스워드 복잡도 검증
     */
    public function validate(string $password, array $config = []): bool
    {
        if (!($config['enabled'] ?? true)) {
            return true;
        }

        $characterTypes = $this->countCharacterTypes($password, $config);
        $passwordLength = mb_strlen($password);

        // 필수 요구사항 체크
        if ($config['require_uppercase'] ?? false) {
            if (!preg_match('/[A-Z]/', $password)) {
                return false;
            }
        }

        if ($config['require_lowercase'] ?? false) {
            if (!preg_match('/[a-z]/', $password)) {
                return false;
            }
        }

        if ($config['require_numbers'] ?? false) {
            if (!preg_match('/[0-9]/', $password)) {
                return false;
            }
        }

        if ($config['require_special'] ?? false) {
            $specialChars = preg_quote($config['special_characters'] ?? '!@#$%^&*()_+-=[]{}|;:,.<>?~`', '/');
            if (!preg_match('/[' . $specialChars . ']/', $password)) {
                return false;
            }
        }

        // 문자 그룹 조합에 따른 최소 길이 체크
        if ($characterTypes >= 4) {
            $minLength = $config['min_length_4_types'] ?? 8;
            return $passwordLength >= $minLength;
        } elseif ($characterTypes >= 3) {
            $minLength = $config['min_length_3_types'] ?? 8;
            return $passwordLength >= $minLength;
        } elseif ($characterTypes >= 2) {
            $minLength = $config['min_length_2_types'] ?? 10;
            return $passwordLength >= $minLength;
        }

        // 1가지 이하 조합은 허용하지 않음
        return false;
    }

    /**
     * 사용된 문자 그룹 개수 세기
     */
    protected function countCharacterTypes(string $password, array $config): int
    {
        $types = 0;

        // 대문자
        if (preg_match('/[A-Z]/', $password)) {
            $types++;
        }

        // 소문자
        if (preg_match('/[a-z]/', $password)) {
            $types++;
        }

        // 숫자
        if (preg_match('/[0-9]/', $password)) {
            $types++;
        }

        // 특수문자
        $specialChars = preg_quote($config['special_characters'] ?? '!@#$%^&*()_+-=[]{}|;:,.<>?~`', '/');
        if (preg_match('/[' . $specialChars . ']/', $password)) {
            $types++;
        }

        return $types;
    }

    /**
     * 에러 메시지 가져오기
     */
    public function getMessage(array $config = []): string
    {
        return trans('password-security::validation.complexity_failed');
    }

    /**
     * 상세 에러 메시지 가져오기
     */
    public function getDetailedMessage(string $password, array $config = []): string
    {
        $characterTypes = $this->countCharacterTypes($password, $config);
        $passwordLength = mb_strlen($password);

        if ($characterTypes >= 3) {
            $minLength = $config['min_length_3_types'] ?? 8;
            if ($passwordLength < $minLength) {
                return trans('password-security::validation.complexity_3_types_length', ['min' => $minLength]);
            }
        } elseif ($characterTypes >= 2) {
            $minLength = $config['min_length_2_types'] ?? 10;
            if ($passwordLength < $minLength) {
                return trans('password-security::validation.complexity_2_types_length', ['min' => $minLength]);
            }
        } else {
            return trans('password-security::validation.complexity_min_types');
        }

        // 필수 요구사항 체크
        $missing = [];
        if (($config['require_uppercase'] ?? false) && !preg_match('/[A-Z]/', $password)) {
            $missing[] = trans('password-security::validation.uppercase');
        }
        if (($config['require_lowercase'] ?? false) && !preg_match('/[a-z]/', $password)) {
            $missing[] = trans('password-security::validation.lowercase');
        }
        if (($config['require_numbers'] ?? false) && !preg_match('/[0-9]/', $password)) {
            $missing[] = trans('password-security::validation.numbers');
        }
        if (($config['require_special'] ?? false)) {
            $specialChars = preg_quote($config['special_characters'] ?? '!@#$%^&*()_+-=[]{}|;:,.<>?~`', '/');
            if (!preg_match('/[' . $specialChars . ']/', $password)) {
                $missing[] = trans('password-security::validation.special_characters');
            }
        }

        if (!empty($missing)) {
            return trans('password-security::validation.complexity_missing', ['types' => implode(', ', $missing)]);
        }

        return trans('password-security::validation.complexity_failed');
    }
}

