<?php

namespace CmsOrbit\PasswordSecurity\Validators\Rules;

class CommonPatternRule
{
    /**
     * 추측 가능한 패턴 검증
     */
    public function validate(string $password, array $config = []): bool
    {
        if (!($config['enabled'] ?? true)) {
            return true;
        }

        $password = strtolower($password);

        // 연속 숫자 체크
        if ($config['block_sequential_numbers'] ?? true) {
            if ($this->hasSequentialNumbers($password, $config['sequential_threshold'] ?? 4)) {
                return false;
            }
        }

        // 연속 문자 체크
        if ($config['block_sequential_letters'] ?? true) {
            if ($this->hasSequentialLetters($password, $config['sequential_threshold'] ?? 4)) {
                return false;
            }
        }

        // 반복 문자 체크
        if ($config['block_repeated_characters'] ?? true) {
            if ($this->hasRepeatedCharacters($password, $config['repeated_char_threshold'] ?? 3)) {
                return false;
            }
        }

        // 키보드 패턴 체크
        if ($config['block_keyboard_patterns'] ?? true) {
            if ($this->hasKeyboardPattern($password, $config['keyboard_patterns'] ?? [])) {
                return false;
            }
        }

        // 일반 단어 체크
        if ($config['block_common_words'] ?? true) {
            if ($this->hasCommonWord($password, $config['common_words_list'] ?? [])) {
                return false;
            }
        }

        // 생일 패턴 체크
        if ($config['block_birthday_patterns'] ?? true) {
            if ($this->hasBirthdayPattern($password)) {
                return false;
            }
        }

        // 전화번호 패턴 체크
        if ($config['block_phone_patterns'] ?? true) {
            if ($this->hasPhonePattern($password)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 연속 숫자 검사
     */
    protected function hasSequentialNumbers(string $password, int $threshold = 4): bool
    {
        $sequences = [
            '0123456789',
            '9876543210',
        ];

        foreach ($sequences as $sequence) {
            for ($i = 0; $i <= strlen($sequence) - $threshold; $i++) {
                $substr = substr($sequence, $i, $threshold);
                if (str_contains($password, $substr)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 연속 문자 검사
     */
    protected function hasSequentialLetters(string $password, int $threshold = 4): bool
    {
        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'zyxwvutsrqponmlkjihgfedcba',
        ];

        foreach ($sequences as $sequence) {
            for ($i = 0; $i <= strlen($sequence) - $threshold; $i++) {
                $substr = substr($sequence, $i, $threshold);
                if (str_contains($password, $substr)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 반복 문자 검사
     */
    protected function hasRepeatedCharacters(string $password, int $threshold = 3): bool
    {
        return preg_match('/(.)\1{' . ($threshold - 1) . ',}/', $password) === 1;
    }

    /**
     * 키보드 패턴 검사
     */
    protected function hasKeyboardPattern(string $password, array $patterns = []): bool
    {
        $defaultPatterns = [
            'qwerty', 'qwertyuiop', 'asdfgh', 'asdfghjkl', 'zxcvbn', 'zxcvbnm',
            'qazwsx', 'qaz', 'wsx', 'edc', 'rfv', 'tgb', 'yhn', 'ujm',
        ];

        $patterns = array_merge($defaultPatterns, $patterns);

        foreach ($patterns as $pattern) {
            if (str_contains($password, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 일반 단어 검사
     */
    protected function hasCommonWord(string $password, array $words = []): bool
    {
        $defaultWords = [
            'password', 'admin', 'welcome', 'qwerty', 'letmein',
            '1234', '12345', '123456', '1234567', '12345678',
            'pass', 'user', 'guest', 'test', 'demo',
        ];

        $words = array_merge($defaultWords, $words);

        foreach ($words as $word) {
            if (str_contains($password, strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 생일 패턴 검사
     */
    protected function hasBirthdayPattern(string $password): bool
    {
        // YYYYMMDD, YYMMDD 형식
        $patterns = [
            '/19\d{6}/',  // 1900~1999
            '/20\d{6}/',  // 2000~2099
            '/\d{6}/',    // YYMMDD
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 전화번호 패턴 검사
     */
    protected function hasPhonePattern(string $password): bool
    {
        // 한국 전화번호 패턴
        $patterns = [
            '/010\d{8}/',     // 010XXXXXXXX
            '/01[016789]\d{7,8}/', // 구형 휴대폰 번호
            '/02\d{7,8}/',    // 서울 지역번호
            '/0[3-6][1-5]\d{7,8}/', // 기타 지역번호
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $password)) {
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
        return trans('password-security::validation.common_pattern_detected');
    }
}

