<?php

return [
    'required' => ':attribute 항목은 필수입니다.',
    'string' => ':attribute 항목은 문자열이어야 합니다.',
    'email' => ':attribute 항목은 올바른 이메일 주소여야 합니다.',
    'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    'current_password' => '비밀번호가 올바르지 않습니다.',
    'unique' => '이미 사용 중인 :attribute입니다.',
    'max' => [
        'string' => ':attribute 항목은 :max자를 초과할 수 없습니다.',
    ],
    'min' => [
        'string' => ':attribute 항목은 최소 :min자여야 합니다.',
    ],
    'size' => [
        'string' => ':attribute 항목은 정확히 :size자여야 합니다.',
    ],
    'password' => [
        'letters' => ':attribute 항목에는 영문자가 최소 1자 이상 포함되어야 합니다.',
        'mixed' => ':attribute 항목에는 대문자와 소문자가 각각 1자 이상 포함되어야 합니다.',
        'numbers' => ':attribute 항목에는 숫자가 최소 1자 이상 포함되어야 합니다.',
        'symbols' => ':attribute 항목에는 기호가 최소 1자 이상 포함되어야 합니다.',
        'uncompromised' => '유출 이력이 있는 :attribute는 사용할 수 없습니다. 다른 :attribute를 선택해 주세요.',
    ],
    'attributes' => [
        'name' => '이름',
        'email' => '이메일',
        'password' => '비밀번호',
        'current_password' => '현재 비밀번호',
        'password_confirmation' => '비밀번호 확인',
        'code' => '인증 코드',
    ],
];
