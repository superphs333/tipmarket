<?php

namespace App\Livewire\Console\Tips;

use App\Livewire\Concerns\ManagesTipListFilters;
use App\Models\Category;
use App\Models\Tip;
use App\Queries\Tips\TipListQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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

    // 현재 선택된 팁 ID 목록
    public array $selectedTipIds = [];

    // 현재 화면의 전체 선택 체크박스 상태
    public bool $selectAll = false;

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
     * 상단 전체 선택 체크박스를 눌렀을 때 실행됨
     */
    public function toggleSelectAll() : void
    {
        $visibleIds = $this->visibleTipIds();

        // 모든 항목이 이미 선택된 상태 > 전체 해제
        if($this->selectAll){
            $this->selectedTipIds = array_values(
                array_diff($this->selectedTipIds, $visibleIds)
            ); // 전부 빠지게 됨.

            $this->selectAll = false;

            return;
        }

        // 전체 선택이 아니면 -> 현재 화면에 보이는 모든 ID를 선택 목록에 추가
        $this->selectedTipIds = array_values(array_unique([
            ...$this->selectedTipIds,
            ...$visibleIds,
        ]));

        $this->selectAll = $this->hasSelectedAllVisibleTips();
    }

    /**
     * 개별 체크박스를 변경하면 전체 선택 체크박스 상태도 다시 계산
     */
    public function updatedSelectedTipIds() : void
    {
        $this->selectAll = $this->hasSelectedAllVisibleTips();
    }

    /**
     * 현재 화면의 모든 팁이 선택되어 있는지 계산
     */
    private function hasSelectedAllVisibleTips() : bool
    {
        $visibleIds = $this->visibleTipIds();

        return count($visibleIds) > 0 && empty(array_diff($visibleIds, $this->selectedTipIds));
    }

    /**
     * 현재 페이지에 보이는 팁 ID만 문자열 배열로 반환
     *
     * @return array<int, string>
     */
    private function visibleTipIds(): array
    {
        return collect($this->tips()->items())
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
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
