<?php
/**
 * [이미지 업로드 정책 모아두기]
 * ex) 용도별 최대 용량, 허용 MIME, 저장 디스크, 공개 여부 등
 */


return [
    // 파일을 어디에 저장할지 정함
    'disk' => env('MEDIA_DISK', 'r2'),

    // 이미지 사용 목적별 정책
    'collections' => [
        // 프로필 이미지 정책
        'profile_avatar' => [
            'max_size' => 2 * 1024 * 1024,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'visibility' => 'public',
        ],
        // 팁 대표 이미지 정책
        'tip_thumbnail' => [
            'max_size' => 5 * 1024 * 1024,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'visibility' => 'public',
        ],
        // 질문 대표 이미지 정책
        'question_thumbnail' => [
            'max_size' => 5 * 1024 * 1024,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'visibility' => 'public',
        ],
    ],
];
