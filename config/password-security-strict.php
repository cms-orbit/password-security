<?php

/**
 * 엄격한 보안 정책 예제
 *
 * 공공기관/금융권 수준의 보안 요구사항을 충족합니다.
 * 이 파일을 config/password-security.php 로 복사하여 사용하세요.
 */

return [
    'enabled' => env('PASSWORD_SECURITY_ENABLED', true),

    'models' => [
        \App\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 엄격한 복잡도 규칙
    |--------------------------------------------------------------------------
    | 권장: 4가지 모두 필수로 설정
    */
    'complexity' => [
        'enabled' => true,
        'min_length_2_types' => 10,
        'min_length_3_types' => 8,
        'min_length_4_types' => 8,
        'require_uppercase' => true,         // 대문자 필수
        'require_lowercase' => true,         // 소문자 필수
        'require_numbers' => true,           // 숫자 필수
        'require_special' => true,           // 특수문자 필수
        'special_characters' => '!@#$%^&*()_+-=[]{}|;:,.<>?~`',
    ],

    'common_patterns' => [
        'enabled' => true,
        'block_sequential_numbers' => true,
        'block_sequential_letters' => true,
        'block_repeated_characters' => true,
        'repeated_char_threshold' => 2,      // 3개 이상 반복 차단
        'block_keyboard_patterns' => true,
        'block_common_words' => true,
        'block_birthday_patterns' => true,
        'block_phone_patterns' => true,
        'sequential_threshold' => 3,         // 4개 이상 연속 차단
        'common_words_list' => [
            'password', 'admin', 'welcome', 'qwerty', 'letmein',
            '1234', '12345', '123456', '1234567', '12345678', '123123',
            'pass', 'user', 'guest', 'test', 'demo', 'abc', 'qwer', 'asd',
        ],
        'keyboard_patterns' => [
            'qwerty', 'qwertyuiop', 'asdfgh', 'asdfghjkl', 'zxcvbn', 'zxcvbnm',
            'qazwsx', 'qaz', 'wsx', 'edc', 'rfv', 'tgb', 'yhn', 'ujm',
        ],
    ],

    'personal_info' => [
        'enabled' => true,
        'block_name' => true,
        'block_email' => true,
        'block_username' => true,
        'additional_fields' => [],
        'min_substring_length' => 3,
        'case_insensitive' => true,
    ],

    'history' => [
        'enabled' => true,
        'check_last_n_passwords' => 3,       // 최근 3개 재사용 금지
        'keep_history_for_days' => 365,
        'auto_cleanup' => true,
    ],

    'expiration' => [
        'enabled' => true,
        'expires_in_days' => 90,             // 분기 1회 변경
        'notify_before_days' => [14, 7, 3, 1],
        'grace_period_days' => 0,
        'force_change_on_first_login' => false,
        'force_change_route' => 'password.change',
        'force_change_url' => '/password/change',
        'excluded_routes' => [
            'password.change',
            'password.update',
            'logout',
        ],
        'excluded_urls' => [
            'password/*',
            'api/*',
        ],
    ],

    'inactive_accounts' => [
        'enabled' => true,
        'inactive_days' => 90,               // 90일 미사용 시 비활성화
        'notify_before_days' => [14, 7, 3],
        'auto_deactivate' => true,
        'delete_after_days' => null,
        'exclusions' => [
            'roles' => ['super-admin'],
            'emails' => [],
            'has_active_sessions' => true,
        ],
        'active_field' => 'is_active',
        'deactivated_at_field' => 'deactivated_at',
    ],

    'exceptions' => [
        'throw_exceptions' => true,
        'log_violations' => true,
        'log_channel' => null,
    ],

    'notifications' => [
        'enabled' => true,
        'channels' => ['mail'],
        'expiration' => [
            'enabled' => true,
            'mail_subject' => 'Password Expiration Notice',
        ],
        'inactive_account' => [
            'enabled' => true,
            'mail_subject' => 'Account Inactivity Notice',
        ],
    ],

    'tables' => [
        'password_securities' => 'password_securities',
        'password_histories' => 'password_histories',
    ],
];

