@props([
    // type : 어떤 관리 목록에서 이 액션 바를 쓰는지 구분 => 템플릿 내부에서 보여줄 액션 구역이 달라짐.
    'type',
    // 현재 선택된 항목 갯수 
    'selectedCount' => 0,

    /**
     * 부모 Livewire 컴포넌트의 현재 벌크 작업 상태  
     */
    'bulkAction' => '',
    'bulkValue' => '',

    /**
     *  팁 관련 옵션
     */
    // 상태 변경 select에 사용할 옵션 
    'statusOptions' => [],
    // 노출 변경 select
    'audienceOptions' => [],

])


@if ($selectedCount > 0)
    <div class="border-b border-zinc-100 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/60">
        <div class="rounded-lg border border-zinc-200 bg-white px-4 py-3 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
            <!-- 선택 개수 -->
            <div class="mb-5 flex items-center gap-2 whitespace-nowrap text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                <input type="checkbox" checked disabled class="rounded border-zinc-300 text-blue-600">
                <span>{{ number_format($selectedCount) }}개 선택됨</span>
            </div>

            <!-- 액션 영역 =>type에 따라 내부 액션이 달라짐 -->
            <!-- 팁 -->
            @if ($type === 'tip')
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- 작업 선택 -->
                        <select
                            wire:model.live="bulkAction"
                            class="h-9 w-36 rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-800 shadow-none focus:border-zinc-400 focus:ring-0 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">작업 선택</option>
                            <option value="status">상태 변경</option>
                            <option value="audience">노출 변경</option>
                        </select>

                        <!-- 변경 값 선택 -->
                        <select
                            wire:model.live="bulkValue"
                            class="h-9 w-40 rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-800 shadow-none focus:border-zinc-400 focus:ring-0 disabled:bg-zinc-100 disabled:text-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:disabled:bg-zinc-800"
                            @disabled(blank($bulkAction))
                        >
                            <option value="">변경 값 선택</option>
                            @if ($bulkAction === 'status')
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @endif

                            @if ($bulkAction === 'audience')
                                @foreach ($audienceOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>

                        <!-- 적용 버튼 -->
                        <flux:button
                            type="button"
                            variant="primary"
                            size="sm"
                            wire:click="applyBulkAction"
                            :disabled="blank($bulkAction) || blank($bulkValue)"
                        >
                            적용
                        </flux:button>
                    </div>

                    <!-- 삭제는 변경/해제 액션과 분리해서 오른쪽 끝에 둔다. -->
                    <flux:button
                        type="button"
                        variant="danger"
                        size="sm"
                        wire:click="deleteSelected"
                        wire:confirm="선택한 팁을 삭제하시겠습니까?"
                    >
                        삭제
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
@endif
