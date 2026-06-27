{{-- 콘솔 전용 레이아웃 안에서 팁 운영 목록 화면을 렌더링한다. --}}
<x-layouts::console :title="__('TIPS')">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Tips 관리</flux:heading>
                    <flux:text class="mt-3 text-base text-zinc-500 dark:text-zinc-400">
                        총 {{ number_format($tipsTotal) }}개
                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">|</span>
                        최근 수정:
                        {{ $latestTipUpdatedDate ?? '없음' }}
                    </flux:text>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <flux:modal.trigger name="ai-tip-create">
                        <flux:button
                            type="button"
                            variant="outline"
                            icon="sparkles"
                            class="w-full sm:w-auto"
                        >
                            AI로 팁 추가
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:button
                        type="button"
                        variant="primary"
                        icon="plus"
                        class="w-full sm:w-auto"
                    >
                        Tip 추가
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    @include('console.tips.modals.ai-create', [
        'categories' => $categories,
    ])
</x-layouts::console>
