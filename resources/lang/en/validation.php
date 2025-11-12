<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Security Validation Messages (English)
    |--------------------------------------------------------------------------
    */

    'validation_failed' => 'Password does not meet security policy requirements.',

    // Complexity Rule
    'complexity_failed' => 'Password complexity is insufficient.',
    'complexity_2_types_length' => 'When using 2 character groups, password must be at least :min characters long.',
    'complexity_3_types_length' => 'When using 3 character groups, password must be at least :min characters long.',
    'complexity_min_types' => 'Password must contain at least 2 different character groups (uppercase, lowercase, numbers, special characters).',
    'complexity_missing' => 'Password must include the following character groups: :types',
    
    'uppercase' => 'uppercase letters',
    'lowercase' => 'lowercase letters',
    'numbers' => 'numbers',
    'special_characters' => 'special characters',

    // Common Pattern Rule
    'common_pattern_detected' => 'Easily guessable password pattern detected. (sequential characters/numbers, repeated characters, keyboard patterns, common words, birthdays/phone numbers, etc.)',

    // Personal Info Rule
    'personal_info_detected' => 'Password contains personal information (name, email, etc.).',

    // History Rule
    'password_in_history' => 'Cannot reuse the last :count passwords.',
];

