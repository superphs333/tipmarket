<?php

namespace App\Livewire\Concerns;

/**
 * 관리 목록에서 공통으로 사용하는 선택 상태 관리 기능.
 *
 * 실제 삭제, 상태 변경, 노출 변경, 권한 검사는 대상마다 규칙이 다르므로
 * 이 trait이 아니라 각 Livewire 컴포넌트나 Action 클래스에서 처리한다.
 */
trait ManagesBulkSelection
{
    /**
     * 선택된 항목 ID 목록.
     *
     * Livewire checkbox value는 문자열로 전달되는 경우가 많으므로
     * 비교 안정성을 위해 선택 ID도 문자열 배열로 다룬다.
     *
     * @var array<int, string>
     */
    public array $selectedIds = [];

    /**
     * 현재 화면에 보이는 항목이 모두 선택되어 있는지 여부.
     */
    public bool $selectAll = false;

    public string $bulkAction = '';

    public string $bulkValue = '';

    /**
     * 상단 전체 선택 체크박스를 눌렀을 때 실행된다.
     */
    public function toggleSelectAll(): void
    {
        $visibleIds = $this->visibleIds();

        if ($this->selectAll) {
            $this->selectedIds = array_values(
                array_diff($this->selectedIds, $visibleIds)
            );

            $this->selectAll = false;

            return;
        }

        $this->selectedIds = array_values(array_unique([
            ...$this->selectedIds,
            ...$visibleIds,
        ]));

        $this->selectAll = $this->hasSelectedAllVisibleItems();
    }

    /**
     * 개별 체크박스 선택 상태가 바뀔 때 전체 선택 상태를 다시 계산한다.
     */
    public function updatedSelectedIds(): void
    {
        $this->selectAll = $this->hasSelectedAllVisibleItems();
    }

    /**
     * 선택 상태를 초기화한다.
     */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    /**
     * 현재 화면에 보이는 모든 항목이 선택되어 있는지 확인한다.
     */
    private function hasSelectedAllVisibleItems(): bool
    {
        $visibleIds = $this->visibleIds();

        return count($visibleIds) > 0
            && empty(array_diff($visibleIds, $this->selectedIds));
    }

    /**
     * 벌크 작업 종류가 바뀌면 기존 변경 값을 초기화한다.
     */
    public function updatedBulkAction(): void
    {
        $this->bulkValue = '';
    }

    /**
     * 현재 화면에 보이는 항목 ID 목록을 문자열 배열로 반환한다.
     *
     * @return array<int, string>
     */
    abstract protected function visibleIds(): array;

    /**
     * 선택된 항목에 벌크 작업을 적용한다.
     */
    public function applyBulkAction(): void
    {
        if (! $this->canApplyBulkAction()) {
            return;
        }

        $this->applySelectedBulkAction();
    }

    /**
     * 선택된 항목을 삭제한다.
     */
    public function deleteSelected(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->deleteSelectedItems();
    }

    abstract protected function applySelectedBulkAction(): void;

    abstract protected function deleteSelectedItems(): void;

    /**
     * 벌크 작업을 적용할 수 있는 상태인지 확인한다.
     */
    protected function canApplyBulkAction(): bool
    {
        return $this->selectedIds !== []
            && $this->bulkAction !== ''
            && $this->bulkValue !== '';
    }
}
