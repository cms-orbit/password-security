<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Security Validation Messages (Korean)
    |--------------------------------------------------------------------------
    */

    'validation_failed' => '패스워드 보안 정책을 충족하지 않습니다.',

    // Complexity Rule
    'complexity_failed' => '패스워드 복잡도가 부족합니다.',
    'complexity_2_types_length' => '2가지 문자 그룹 조합 시 최소 :min자 이상이어야 합니다.',
    'complexity_3_types_length' => '3가지 문자 그룹 조합 시 최소 :min자 이상이어야 합니다.',
    'complexity_min_types' => '최소 2가지 이상의 문자 그룹(대문자, 소문자, 숫자, 특수문자)을 조합해야 합니다.',
    'complexity_missing' => '다음 문자 그룹이 포함되어야 합니다: :types',
    
    'uppercase' => '영문 대문자',
    'lowercase' => '영문 소문자',
    'numbers' => '숫자',
    'special_characters' => '특수문자',

    // Common Pattern Rule
    'common_pattern_detected' => '추측하기 쉬운 패스워드 패턴이 감지되었습니다. (연속 문자/숫자, 반복 문자, 키보드 패턴, 일반 단어, 생일/전화번호 등)',

    // Personal Info Rule
    'personal_info_detected' => '패스워드에 개인정보(이름, 이메일 등)가 포함되어 있습니다.',

    // History Rule
    'password_in_history' => '최근 :count개의 패스워드는 재사용할 수 없습니다.',
];

