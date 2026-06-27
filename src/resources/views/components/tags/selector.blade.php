{{-- 태그 선택기에서 화면별로 바꿔 쓸 수 있는 기본 문구와 더미 데이터 --}}
@props([
    'label' => '태그',
    'placeholder' => '태그 이름 검색...',
    'searchTitle' => '검색 결과',
    'searchMeta' => '더미 데이터',
    'recommendedTitle' => '추천 태그',
    'selectedTitle' => '선택된 태그',
    'selectedCount' => 2,
    'maxCount' => 5,
    'helperText' => null,
    'results' => [
        ['name' => '전세'],
        ['name' => '전세 계약'],
        ['name' => '전세 체크리스트'],
        ['name' => '월세'],
        ['name' => '부동산 계약'],
        ['name' => '보증금'],
    ],
    'recommended' => ['청소', '자취', '부동산', '수납'],
    'selected' => [
        ['name' => '전세', 'color' => 'blue'],
        ['name' => '계약 체크리스트', 'color' => 'emerald'],
    ],
])

{{-- 별도 안내 문구가 없으면 최대 선택 개수를 기준으로 기본 안내 문구를 만든다. --}}
@php
    $helperText ??= "태그는 선택 사항이며 최대 {$maxCount}개까지 선택할 수 있습니다.";
    $selectedNames = collect($selected)->pluck('name')->all();
@endphp

{{-- 태그 선택기 전체 컨테이너 --}}
<div {{ $attributes->class('space-y-4') }}>
    {{-- 검색창과 검색 결과 dropdown 영역. 실제 동작에서는 Enter 검색 후 결과 목록을 열어준다. --}}
    <div class="space-y-2">
        {{-- 태그 이름을 입력하는 검색창. 실제 검색 동작은 이후 JS/Livewire/API와 연결한다. --}}
        <flux:input
            :label="$label"
            icon="magnifying-glass"
            :placeholder="$placeholder"
        />

        {{-- 검색창 바로 아래에 붙는 검색 결과 목록. 현재는 더미 데이터이며, 나중에 API 응답으로 대체한다. --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            {{-- 검색 결과 섹션 제목과 보조 상태 문구 --}}
            <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                    {{ $searchTitle }}
                </flux:text>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $searchMeta }}
                </flux:text>
            </div>

            <div class="max-h-72 overflow-y-auto py-1">
                @foreach($results as $result)
                    @php
                        $isSelected = in_array($result['name'], $selectedNames, true);
                    @endphp

                    {{-- 검색 결과 한 줄. 클릭하면 선택 태그에 추가되는 동작을 붙일 예정. --}}
                    <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon.magnifying-glass class="size-4" />
                            </span>
                            <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $result['name'] }}
                            </span>
                        </span>

                        @if($isSelected)
                            <flux:badge size="sm" color="zinc">선택됨</flux:badge>
                        @else
                            <flux:badge size="sm" color="blue" icon="plus">추가</flux:badge>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 검색 전 또는 보조 선택용으로 보여줄 추천 태그 목록 --}}
    <div class="space-y-2">
        <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
            {{ $recommendedTitle }}
        </flux:text>
        <div class="flex flex-wrap gap-2">
            @foreach($recommended as $tag)
                <flux:button type="button" size="sm" variant="filled">{{ $tag }}</flux:button>
            @endforeach
        </div>
    </div>

    {{-- 사용자가 선택한 태그 목록과 선택 개수 안내 영역 --}}
    <div class="space-y-2">
        {{-- 선택된 태그 섹션 제목과 현재 선택 개수 --}}
        <div class="flex items-center justify-between gap-3">
            <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                {{ $selectedTitle }}
            </flux:text>
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $selectedCount }} / {{ $maxCount }}
            </flux:text>
        </div>

        {{-- 선택된 태그 chip 목록. 선택 항목이 없을 때의 empty state는 이후 상태 연결 시 추가한다. --}}
        <div class="min-h-24 rounded-lg border border-dashed border-zinc-200 p-3 dark:border-zinc-700">
            <div class="flex flex-wrap gap-2">
                @foreach($selected as $tag)
                    {{-- 선택된 태그 chip. x 버튼에는 제거 동작을 붙일 예정. --}}
                    <flux:badge :color="$tag['color'] ?? 'zinc'" class="gap-1">
                        {{ $tag['name'] }}
                        <button type="button" aria-label="{{ $tag['name'] }} 태그 제거">x</button>
                    </flux:badge>
                @endforeach
            </div>
        </div>

        {{-- 선택 제한, 선택 옵션 여부 같은 보조 안내 문구 --}}
        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ $helperText }}
        </flux:text>
    </div>
</div>
