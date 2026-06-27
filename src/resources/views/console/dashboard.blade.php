{{-- 콘솔 전용 레이아웃을 사용한다. 일반 사용자 앱 레이아웃과 분리되어 콘솔 메뉴만 노출된다. --}}
<x-layouts::console :title="__('Console Dashboard')">
    {{-- 콘솔 대시보드 본문 컨테이너다. 위에서 아래로 제목 영역과 지표 영역을 배치한다. --}}
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        {{-- 화면 상단 제목 영역이다. 콘솔의 현재 위치와 목적을 사용자에게 보여준다. --}}
        <div>
            <flux:heading size="xl">{{ __('Console Dashboard') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
            </flux:text>
        </div>

        {{-- 운영 현황 요약 카드 영역이다. 화면 폭이 넓으면 3열 그리드로 표시된다. --}}
        <div class="grid gap-4 md:grid-cols-3">
            {{-- 사용자 관련 운영 지표 카드다. 추후 전체 사용자 수, 신규 가입자 수 등을 연결할 수 있다. --}}
            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Users') }}</flux:text>
                <flux:heading size="lg" class="mt-2">-</flux:heading>
            </div>

            {{-- 팁 콘텐츠 관련 운영 지표 카드다. 추후 공개/숨김/초안 팁 수 등을 연결할 수 있다. --}}
            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Tips') }}</flux:text>
                <flux:heading size="lg" class="mt-2">-</flux:heading>
            </div>

            {{-- 신고 처리 관련 운영 지표 카드다. 추후 대기 중인 신고 수 등을 연결할 수 있다. --}}
            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Reports') }}</flux:text>
                <flux:heading size="lg" class="mt-2">-</flux:heading>
            </div>
        </div>
    </div>
</x-layouts::console>
