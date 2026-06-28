<?php

namespace App\Services\Tags;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

/**
 * 태그 이름으로 활성 태그 검색
 * 
 * @param string $query 사용자가 입력한 검색어
 * @param int $limit 최대 결과 개수, 기본 10개
 * @return Collection<int, Tag> 검색된 Tag 모델 컬렉션 
 */
class TagSearchService
{
    public function search(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return new Collection;
        }

        return Tag::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);
    }
}
