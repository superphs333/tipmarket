{{-- 콘솔 전용 팁 수정 화면이다. 실제 입력 폼은 공유 템플릿으로 분리해 붙일 예정이다. --}}
<x-layouts::console :title="__('Tip 수정')">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Tip 수정</flux:heading>
                    <flux:text class="mt-3 text-base text-zinc-500 dark:text-zinc-400">
                        #{{ $tip->id }}
                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">|</span>
                        {{ $tip->title }}
                    </flux:text>
                </div>

                <flux:button
                    :href="route('console.tips.index')"
                    wire:navigate
                    variant="outline"
                    icon="arrow-left"
                >
                    목록으로
                </flux:button>
            </div>
        </div>

        <div class="rounded-lg border border-dashed border-zinc-200 bg-white p-6 text-sm text-zinc-500 shadow-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
            수정 폼은 다음 단계에서 공유 템플릿으로 연결합니다.
        </div>
    </div>
</x-layouts::console>
