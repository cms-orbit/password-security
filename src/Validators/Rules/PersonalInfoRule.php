<?php

namespace CmsOrbit\PasswordSecurity\Validators\Rules;

class PersonalInfoRule
{
    /**
     * 개인정보 포함 여부 검증
     */
    public function validate(string $password, $model, array $config = []): bool
    {
        if (!($config['enabled'] ?? true)) {
            return true;
        }

        $password = $config['case_insensitive'] ?? true ? strtolower($password) : $password;
        $minLength = $config['min_substring_length'] ?? 3;

        // 모델에서 개인정보 필드 가져오기
        $personalInfoFields = method_exists($model, 'getPersonalInfoFields') 
            ? $model->getPersonalInfoFields() 
            : config('password-security.defaults.personal_info_fields', []);

        // 이름 체크
        if (($config['block_name'] ?? true) && isset($personalInfoFields['name'])) {
            $name = $model->{$personalInfoFields['name']} ?? null;
            if ($name && $this->containsSubstring($password, $name, $minLength, $config['case_insensitive'] ?? true)) {
                return false;
            }
        }

        // 이메일 체크
        if (($config['block_email'] ?? true) && isset($personalInfoFields['email'])) {
            $email = $model->{$personalInfoFields['email']} ?? null;
            if ($email) {
                $emailParts = explode('@', $email);
                $localPart = $emailParts[0] ?? '';
                if ($localPart && $this->containsSubstring($password, $localPart, $minLength, $config['case_insensitive'] ?? true)) {
                    return false;
                }
            }
        }

        // 사용자명 체크
        if (($config['block_username'] ?? true) && isset($personalInfoFields['username'])) {
            $username = $model->{$personalInfoFields['username']} ?? null;
            if ($username && $this->containsSubstring($password, $username, $minLength, $config['case_insensitive'] ?? true)) {
                return false;
            }
        }

        // 추가 필드 체크
        $additionalFields = $config['additional_fields'] ?? [];
        foreach ($additionalFields as $fieldKey) {
            $fieldName = $personalInfoFields[$fieldKey] ?? $fieldKey;
            $value = $model->{$fieldName} ?? null;
            
            if ($value && $this->containsSubstring($password, $value, $minLength, $config['case_insensitive'] ?? true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 부분 문자열 포함 여부 확인
     */
    protected function containsSubstring(string $password, string $value, int $minLength, bool $caseInsensitive = true): bool
    {
        if ($caseInsensitive) {
            $password = strtolower($password);
            $value = strtolower($value);
        }

        // 최소 길이 미만이면 체크하지 않음
        if (mb_strlen($value) < $minLength) {
            return false;
        }

        // 공백과 특수문자 제거
        $cleanValue = preg_replace('/[^a-z0-9]/i', '', $value);
        
        if (mb_strlen($cleanValue) < $minLength) {
            return false;
        }

        // 부분 문자열 포함 여부
        if (str_contains($password, $cleanValue)) {
            return true;
        }

        // 단어 분리 후 체크
        $words = preg_split('/\s+/', $value);
        foreach ($words as $word) {
            $cleanWord = preg_replace('/[^a-z0-9]/i', '', $word);
            if (mb_strlen($cleanWord) >= $minLength && str_contains($password, strtolower($cleanWord))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 에러 메시지 가져오기
     */
    public function getMessage(array $config = []): string
    {
        return trans('password-security::validation.personal_info_detected');
    }
}

