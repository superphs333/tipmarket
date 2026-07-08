<?php

namespace App\Support\Tags;

/**
 * 태그 이름 정규화 규칙을 한 곳에서 관리
 */
final class TagNameNormalizer
{
    /**
     * 단일 태그명을 저장/비교 가능한 표준 형태로 정리
     * : 앞뒤 공백 제거, 앞쪽 # 제거, 모든 공백 문자 제거
     *
     * @return string|null 정규화 후 빈 값이면 null
     *
     * ex) $tagName = $normalizer->normalize('# 전세 계약'); // 결과: '전세계약'
     */
    public function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $tagName = trim($value);
        $tagName = ltrim($tagName, '#');
        $tagName = preg_replace('/\s+/u', '', $tagName) ?? $tagName; // 모든 공백 문자 제거

        return $tagName !== '' ? $tagName : null;
    }

    /**
     * 여러 태그명을 정규화하고 중복을 제거
     *
     * ex) $normalizer->normalizeMany(['# 청소', '청소', '욕실 정리']);
     * => 결과 : ['청소', '욕실정리']
     *
     * @return array<int, string>
     */
    public function normalizeMany(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(fn (mixed $value): ?string => $this->normalize($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 태그명이 자동 생성/선택 가능한 길이인지 확인
     */
    public function isValidLength(mixed $value, int $min = 2, int $max = 50): bool
    {
        $tagName = $this->normalize($value);

        if ($tagName === null) {
            return false;
        }

        $length = mb_strlen($tagName);

        return $length >= $min && $length <= $max;
    }
}
