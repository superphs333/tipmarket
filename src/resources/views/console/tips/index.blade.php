{{-- 콘솔 전용 레이아웃 안에서 팁 운영 목록 화면을 렌더링한다. --}}
<x-layouts::console :title="__('TIPS')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">TIPS</flux:heading>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                {{ __('Manage community tips and content moderation status.') }}
            </flux:text>
        </div>
    </div>
</x-layouts::console>
