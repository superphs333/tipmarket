<?php

namespace App\Actions\Tips;

use App\Models\Tip;

/**
 * 관리자 팁 목록에서 선택된 팁들에 일괄 작업을 적용한다.
 *
 * Livewire 컴포넌트는 선택 상태와 화면 이벤트를 관리하고,
 * 이 Action은 실제 Tip DB 변경만 담당한다.
 */
final class ApplyTipBulkAction
{
    /**
     * 선택된 팁들의 상태를 변경한다.
     *
     * @param  array<int, int|string>  $tipIds
     */
    public function updateStatus(array $tipIds, string $status): int
    {
        if (! Tip::isValidStatus($status)) {
            return 0;
        }

        return $this->updateColumn($tipIds, 'status', $status);
    }

    /**
     * 선택된 팁들의 노출 대상을 변경한다.
     *
     * @param  array<int, int|string>  $tipIds
     */
    public function updateAudience(array $tipIds, string $audience): int
    {
        if (! Tip::isValidAudience($audience)) {
            return 0;
        }

        return $this->updateColumn($tipIds, 'audience', $audience);
    }

    /**
     * 선택된 팁들을 삭제한다.
     *
     * Tip 모델은 SoftDeletes를 사용하므로 deleted_at 값이 채워진다.
     *
     * @param  array<int, int|string>  $tipIds
     */
    public function delete(array $tipIds): int
    {
        return Tip::query()
            ->whereIn('id', $this->normalizeIds($tipIds))
            ->delete();
    }

    /**
     * 선택된 팁들의 특정 컬럼 값을 한 번에 변경한다.
     *
     * 현재 이 메서드는 클래스 내부에서만 호출하므로 컬럼명은 외부 입력이 아니다.
     *
     * @param  array<int, int|string>  $tipIds
     */
    private function updateColumn(array $tipIds, string $column, string $value): int
    {
        return Tip::query()
            ->whereIn('id', $this->normalizeIds($tipIds))
            ->update([$column => $value]);
    }

    /**
     * Livewire checkbox value는 문자열로 들어올 수 있으므로 DB 조회 전에 정리한다.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
