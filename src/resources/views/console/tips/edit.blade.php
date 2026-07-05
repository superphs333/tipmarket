{{-- 콘솔 전용 팁 생성/수정 화면이다. 실제 입력 폼은 공유 템플릿으로 분리한다. --}}
<x-layouts::console :title="__($pageTitle)">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">{{ $pageTitle }}</flux:heading>
                    <flux:text class="mt-3 text-base text-zinc-500 dark:text-zinc-400">
                        @if ($isCreate)
                            {{ $pageDescription }}
                        @else
                            #{{ $tip->id }}
                            <span class="mx-2 text-zinc-300 dark:text-zinc-600">|</span>
                            {{ $tip->title }}
                        @endif
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

        <x-tips.edit-form :tip="$tip" :mode="$mode" />
    </div>
</x-layouts::console>
