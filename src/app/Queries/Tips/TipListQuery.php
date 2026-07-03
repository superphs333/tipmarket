<?php

namespace App\Queries\Tips;

use App\Models\Tip;
use App\Support\Filters\FilterNormalizer;
use App\Support\Tags\TagNameNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * 여러 팁 목록 화면에서 재사용할 팁 조회 쿼리 빌더
 * : 여러 목록 화면에서 공통으로 쓰는 조회 조건 조립. 
 */
final class TipListQuery
{
    private FilterNormalizer $filterNormalizer;
    private TagNameNormalizer $tagNameNormalizer;

    public function __construct(
        ?FilterNormalizer $filterNormalizer = null,
        ?TagNameNormalizer $tagNameNormalizer = null,
    ) {
        $this->filterNormalizer = $filterNormalizer ?? new FilterNormalizer();
        $this->tagNameNormalizer = $tagNameNormalizer ?? new TagNameNormalizer();
    }

    /**
     * 팁 목록 쿼리 객체 
     */
    public static function make() : self
    {
        return new self();
    }

    /**
     * 검색 조건을 적용한 팁 목록을 페이지네이션 
     * 
     * @param  array<string, mixed>  $filters
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15) : LengthAwarePaginator
    {
        $filters = $this->normalizeFilters($filters);
        $query = Tip::query()
            ->with([
                'user:id,name,email',
                'category:id,name',
                'tags:id,name',
            ])
            ->latest('updated_at');

        if($filters['category_id'] !== null) $query->where('category_id', $filters['category_id']);
        if($filters['author_ids'] !== []) $query->whereIn('user_id', $filters['author_ids']);
        if($filters['status']!==null) $query->where('status', $filters['status']);
        if($filters['audience'] !==null) $query->where('audience', $filters['audience']);
        if($filters['created_from']!==null) $query->whereDate('created_at','>=',$filters['created_from']);
        if($filters['created_to']!==null) $query->whereDate('created_at','<=',$filters['created_to']);
        if($filters['tag_names']!==[]){
            // 선택된 태그를 모두 가진 팁만 조회 
            foreach($filters['tag_names'] as $tagName){
                $query->whereHas('tags', function (Builder $tagQuery) use ($tagName) : void {
                    $tagQuery->where('name',$tagName);
                });
            }
        }
        if($filters['keyword']!==null){
            $keyword = $filters['keyword'];
            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($keyword) : void {
                        $userQuery
                            ->where('name','like',"%{$keyword}%")
                            ->orWhere('email','like',"%{$keyword}%");
                    });
            });
        }
        return $query->paginate($perPage);


    } 

    /**
     * 외부에서 들어온 검색 조건을 쿼리에 바로 넣을 수 있는 형태로 정리
     * 
     * @param array<string, mixed> $filters
     * @return array{
     *     category_id: int|null,
     *     author_ids: array<int, int>,
     *     tag_names: array<int, string>,
     *     status: string|null,
     *     audience: string|null,
     *     created_from: string|null,
     *     created_to: string|null,
     *     keyword: string|null
     * }
     */
    private function normalizeFilters(array $filters) : array
    {
        return[
            'category_id' => $this->filterNormalizer->positiveInt($filters['category_id'] ?? null),
            'author_ids' => $this->filterNormalizer->positiveInts($filters['author_ids'] ?? []),
            'tag_names' => $this->tagNameNormalizer->normalizeMany($filters['tag_names'] ?? []),
            'status' => $this->filterNormalizer->allowedString($filters['status'] ?? null, Tip::STATUSES),
            'audience' => $this->filterNormalizer->allowedString($filters['audience'] ?? null, Tip::AUDIENCES),
            'created_from' => $this->filterNormalizer->date($filters['created_from'] ?? null),
            'created_to' => $this->filterNormalizer->date($filters['created_to'] ?? null),
            'keyword' => $this->filterNormalizer->keyword($filters['keyword'] ?? null),
        ];
    }
}
