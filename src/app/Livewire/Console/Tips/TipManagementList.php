<?php

namespace App\Livewire\Console\Tips;

use App\Livewire\Concerns\ManagesTipListFilters;
use App\Models\Category;
use App\Models\Tip;
use App\Queries\Tips\TipListQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Livewire\Component;

/**
 * 관리자 팁관리 화면의 목록/검색 UI 상태를 담당
 * - 관리자 팁관리 화면 렌더링
 * - 관리자 화면에서 사용할 카테고리/상태/노출 옵션 전달
 * - 공통 필터 상태를 TipListQuery에 넘겨 목록 조회
 */
class TipManagementList extends Component
{
    use ManagesTipListFilters;

    public function render() : View
    {
        return view('livewire.console.tips.tip-management-list',[
            'categories' => $this->categories(),
            'statusOptions' => $this->statusOptions(),
            'audienceOptions' => $this->audienceOptions(),
            'tips' => $this->tips(),
        ]);
    }

    /**
     * 현재 검색 조건이 적용된 팁 목록을 가져옴. 
     */
    private function tips() : LengthAwarePaginator
    {
        return TipListQuery::make()
            ->paginate($this->tipListFilters(), 15);
    }

    /**
     * 카테고리 선택 옵션을 가져옴. 
     * 
     * @return Collection<int, Category>
     */
    private function categories() : Collection
    {
        return Category::query()
            ->forSelect()
            ->get();
    }

    /**
     * 관리자 팁관리 화면에서 보여줄 상태 필터 라벨을 반환
     * 
     * @return array<string, string>
     */
    private  function statusOptions() : array
    {
        return[
            Tip::STATUS_DRAFT => '임시저장',
            Tip::STATUS_PUBLISHED => '발행'
        ];
    }


    /**
     * 관리자 팁관리 화면에서 보여줄 노출 필터 라벨을 반환한다.
     *
     * @return array<string, string>
     */
    private function audienceOptions(): array
    {
        return [
            Tip::AUDIENCE_PUBLIC => '전체 공개',
            Tip::AUDIENCE_PREMIUM => '프리미엄',
            Tip::AUDIENCE_PRIVATE => '비공개',
        ];
    }
}