<?php

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

/**
 * 팁 목록 Livewire 컴포넌트에서 반복되는 필터 상태를 관리
 * - 팁 목록 검색에 공통으로 쓰이는 Livewire public property를 제공
 * - 검색/초기화 시 페이지네이션을 첫 페이지로 되돌림
 * - TipListQuery에 넘길 필터 배열을 만든다.
 *
 * 전제)
 * - 이 trait을 사용하는 Livewire 컴포넌트는 WithPagination을 함께 사용해야 한다.
 */
trait ManagesTipListFilters
{
    use WithPagination;

    public string $categoryId = '';

    // 태그 선택기에서 넘어오는 태그명 배열 : array<int, string>
    public array $tagNames = [];

    // 작성자 id 목록 : array<int, int|string>
    public array $authorIds = [];

    public string $audience = '';

    public string $status = '';

    public string $createdFrom = '';

    public string $createdTo = '';

    public string $keyword = '';

    /**
     * 검색 버튼 클릭 시 현재 조건으로 첫 페이지부터 다시 조회
     */
    public function search(): void
    {
        $this->resetPage();
    }

    /**
     * 모든 검색 조건을 기본값으로 되돌린다.
     */
    public function resetFilters(): void
    {
        // 태그 선택기처럼 wire:model로 연결된 배열 상태까지 함께 비운다.
        $this->categoryId = '';
        $this->tagNames = [];
        $this->authorIds = [];
        $this->audience = '';
        $this->status = '';
        $this->createdFrom = '';
        $this->createdTo = '';
        $this->keyword = '';

        $this->resetPage();
    }

    /**
     * TipListQuery가 받을 필터 배열로 변환
     * ex) TipListQuery::make()->paginate($this->tipListFilters(), 15);
     *
     * @return array<string, mixed>
     */
    protected function tipListFilters(): array
    {
        return [
            'category_id' => $this->categoryId,
            'tag_names' => $this->tagNames,
            'author_ids' => $this->authorIds,
            'status' => $this->status,
            'audience' => $this->audience,
            'created_from' => $this->createdFrom,
            'created_to' => $this->createdTo,
            'keyword' => $this->keyword,
        ];
    }
}
