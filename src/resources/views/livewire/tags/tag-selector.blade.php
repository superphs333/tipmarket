<div class="space-y-4">
    {{-- 검색어 입력 영역 --}}
    <div class="relative">

        <label class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">
            {{ $label }}
        </label>

        {{-- 검색 input --}}
        <div class="relative">
            <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-zinc-400 dark:text-zinc-500" />

            {{-- 검색 --}}
            <input
                type="text"
                class="block h-10 w-full rounded-lg border border-zinc-200 bg-white py-2 pl-10 pr-10 text-sm text-zinc-900 shadow-xs placeholder:text-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900/15 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-white/20"
                placeholder="{{ $placeholder }}"
                {{-- query값 연결 --}}
                wire:model="query"
                {{-- enter 클릭시 => search --}}
                wire:keydown.enter.prevent="search"
            >

            {{-- 닫기 버튼 --}}
            <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                {{-- closeSearch => query 비우고 검색 상태 닫음 --}}
                wire:click="closeSearch"
                {{-- 쿼기락 비워져 있고, 아직 검색도 안 했으면 닫기 버튼 숨김 --}}
                @if ($query === '' && ! $hasSearched) hidden @endif
                aria-label="태그 검색 결과 닫기"
            >
                <flux:icon.x-mark class="size-4" />
            </button>
        </div>

        {{-- 검색 결과 박스 --}}
        @if ($hasSearched)
            {{-- 검색 결과 드롭다운 --}}
            <div class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                {{-- 검색 결과 헤더 : 제목과 메타 정보 --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                    {{-- 예 : "청소" 검색 결과 --}}
                    <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $resultTitle }}
                    </flux:text>
                    {{-- 예 : 3개 결과 / 검색어 부족 --}}
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $resultMeta }}
                    </flux:text>
                </div>

                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400" wire:loading wire:target="search,addTag,addNewTag,removeTag">
                    검색 중입니다...
                </div>

                {{-- 로딩 중이 아닐 때 결과 목록 표시 --}}
                <div class="max-h-72 overflow-y-auto py-1" wire:loading.remove wire:target="search,addTag,addNewTag,removeTag">
                    {{-- 검색 결과 항목 반복 --}}
                    @forelse ($resultItems as $result)
                        {{-- 추가버튼 --}}
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:bg-zinc-800/60"
                            wire:click="addTag({{ $result['id'] }})"
                            @disabled($result['isDisabled'])
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    <flux:icon.magnifying-glass class="size-4" />
                                </span>
                                <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $result['name'] }}</span>
                            </span>

                            <span @class([
                                'inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                                'bg-zinc-400/15 text-zinc-700 dark:bg-zinc-400/40 dark:text-zinc-200' => $result['isSelected'],
                                'bg-blue-400/20 text-blue-800 dark:bg-blue-400/40 dark:text-blue-200' => ! $result['isSelected'],
                            ])>
                                @if (! $result['isSelected'])
                                    <flux:icon.plus class="size-3" />
                                @endif
                                <span>{{ $result['isSelected'] ? '선택됨' : '추가' }}</span>
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $resultMessage }}
                        </div>
                    @endforelse

                    {{-- 태그 추가 CTA --}}
                    @if ($canCreateTag)
                        <div class="border-t border-zinc-100 bg-zinc-50/80 px-2 py-2 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-white hover:shadow-xs dark:hover:bg-zinc-800"
                                wire:click="addNewTag"
                            >
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-700 dark:bg-emerald-400/20 dark:text-emerald-300">
                                        <flux:icon.plus class="size-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            "{{ $creatableTagName }}" 새 태그로 추가
                                        </span>
                                        <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            검색 결과에 없어도 직접 태그를 만들 수 있습니다.
                                        </span>
                                    </span>
                                </span>

                                <span class="inline-flex shrink-0 items-center rounded-md bg-emerald-400/15 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-400/20 dark:text-emerald-300">
                                    신규
                                </span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- 선택된 태그 영역 --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between gap-3">
            <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                선택된 태그
            </flux:text>
            {{-- 현재 선택 개수 / 최대 선택 개수 --}}
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                @if ($maxCount === null)
                    {{ count($selectedTags) }}개 선택됨
                @else
                    {{ count($selectedTags) }} / {{ $maxCount }}
                @endif
            </flux:text>
        </div>

        {{-- 선택된 태그 목록 박스 --}}
        <div class="min-h-24 rounded-lg border border-dashed border-zinc-200 p-3 dark:border-zinc-700">
            @if (count($selectedTags) > 0)
                <div class="flex flex-wrap gap-2">
                    {{-- 선택된 태그들을 badge 형태로 표시 --}}
                    @foreach ($selectedTags as $tag)
                        <span @class([
                            'inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-medium',
                            'bg-blue-400/20 text-blue-800 dark:bg-blue-400/40 dark:text-blue-200' => ! ($tag['isNew'] ?? false),
                            'bg-emerald-400/15 text-emerald-800 dark:bg-emerald-400/20 dark:text-emerald-200' => $tag['isNew'] ?? false,
                        ])>
                            @if ($tag['isNew'] ?? false)
                                <span class="rounded bg-emerald-500/15 px-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-200">
                                    신규
                                </span>
                            @endif
                            <span>{{ $tag['name'] }}</span>
                            <button
                                type="button"
                                @class([
                                    'hover:text-blue-950 dark:hover:text-white' => ! ($tag['isNew'] ?? false),
                                    'text-blue-700 dark:text-blue-200' => ! ($tag['isNew'] ?? false),
                                    'text-emerald-700 hover:text-emerald-950 dark:text-emerald-200 dark:hover:text-white' => $tag['isNew'] ?? false,
                                ])
                                wire:click="removeTag('{{ $tag['id'] }}')"
                                aria-label="{{ $tag['name'] }} 태그 제거"
                            >x</button>
                        </span>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    아직 선택된 태그가 없습니다.
                </div>
            @endif
        </div>

        {{-- 실제 form submit 때 서버로 넘어가는 값 --}}
        @foreach ($selectedTags as $tag)
            @if ($tag['isNew'] ?? false)
                <input type="hidden" name="new_tag_names[]" value="{{ $tag['name'] }}">
            @else
                <input type="hidden" name="{{ $name }}[]" value="{{ $tag['id'] }}">
            @endif
        @endforeach

        {{-- 안내 문구 --}}
        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
            @if ($maxCount === null)
                태그는 선택 사항입니다.
            @else
                태그는 선택 사항이며 최대 {{ $maxCount }}개까지 선택할 수 있습니다.
            @endif
        </flux:text>
    </div>
</div>
