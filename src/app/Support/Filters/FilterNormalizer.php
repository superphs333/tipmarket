<?php

namespace App\Support\Filters;

/**
 * 목록/검색 필터에서 반복되는 입력값 정규화를 담당
 */
final class FilterNormalizer
{
    /**
     * 양수 정수 id 하나를 정규화 
     * => 단일 id 필터를 쿼리에 넣기 전에 사용. 
     * 
     * ex) $normalizer->positiveInt('12'); // 결과 : 12
     * ex) $normalizer->positiveInt('0'); // 결과 : null 
     * 
     * @param mixed $value
     * @return int|null
     */
    public function  positiveInt(mixed $value) : ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    /**
     * 양수 정수  id 배열을 정규화 
     * 
     * ex) $normalizer->positiveInts(['1', '2', '2', null]); // 결과: [1, 2]
     */
    public function positiveInts(mixed $value) : array
    {
        if(!is_array($value)) $value = [$value];

        return collect($value)
            ->map(fn (mixed $item): int => (int) $item)
            ->filter(fn (int $item) : bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 허용 목록에 포함된 문자열만 통과
     */
    public function allowedString(mixed $value, array $allowed): ?string
    {
        if( ! is_string($value)) return null;

        return in_array($value, $allowed, true) ? $value : null;
    }

    /**
     * HTML date input의 YYYY-MM-DD 값을 정규화 
     */
    public function date(mixed $value): ?string
    {
        if(! is_string($value)) return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            ? $value
            : null;
    }

    /**
     * 검색어를 정규화 
     */
    public function keyword(mixed $value): ?string
    {
        if(!is_string($value)) return null;

        $keyword = trim($value);

        return $keyword !== '' ? $keyword : null;
    }
}
