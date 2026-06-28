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

                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400" wire:loading wire:target="search,addTag,removeTag">
                    검색 중입니다...
                </div>

                {{-- 로딩 중이 아닐 때 결과 목록 표시 --}}
                <div class="max-h-72 overflow-y-auto py-1" wire:loading.remove wire:target="search,addTag,removeTag">
                    {{-- $results 컬렉션 반복 --}}
                    @forelse ($results as $result)
                        @php
                            // 현재 검색 결과 태그가 이미 선택된 태그인지 확인 [??]문법 잘 모르겠다.
                            $isSelected = collect($selectedTags)->contains(fn ($tag) => (string) $tag['id'] === (string) $result->id);
                            // 이미 선택됐거나 최대 개수에 도달하면 추가 버튼 비활성화
                            $isDisabled = $isSelected || count($selectedTags) >= $maxCount;
                        @endphp

                        {{-- 추가버튼 --}}
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:bg-zinc-800/60"
                            wire:click="addTag({{ $result->id }})"
                            @disabled($isDisabled)
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    <flux:icon.magnifying-glass class="size-4" />
                                </span>
                                <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $result->name }}</span>
                            </span>

                            <span @class([
                                'inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                                'bg-zinc-400/15 text-zinc-700 dark:bg-zinc-400/40 dark:text-zinc-200' => $isSelected,
                                'bg-blue-400/20 text-blue-800 dark:bg-blue-400/40 dark:text-blue-200' => ! $isSelected,
                            ])>
                                @if (! $isSelected)
                                    <flux:icon.plus class="size-3" />
                                @endif
                                <span>{{ $isSelected ? '선택됨' : '추가' }}</span>
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $resultMessage }}
                        </div>
                    @endforelse
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
                {{ count($selectedTags) }} / {{ $maxCount }}
            </flux:text>
        </div>

        {{-- 선택된 태그 목록 박스 --}}
        <div class="min-h-24 rounded-lg border border-dashed border-zinc-200 p-3 dark:border-zinc-700">
            @if (count($selectedTags) > 0)
                <div class="flex flex-wrap gap-2">
                    {{-- 선택된 태그들을 badge 형태로 표시 --}}
                    @foreach ($selectedTags as $tag)
                        <span class="inline-flex items-center gap-1 rounded-md bg-blue-400/20 px-2 py-1 text-sm font-medium text-blue-800 dark:bg-blue-400/40 dark:text-blue-200">
                            <span>{{ $tag['name'] }}</span>
                            <button
                                type="button"
                                class="text-blue-700 hover:text-blue-950 dark:text-blue-200 dark:hover:text-white"
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
            <input type="hidden" name="{{ $name }}[]" value="{{ $tag['id'] }}">
        @endforeach

        {{-- 안내 문구 --}}
        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
            태그는 선택 사항이며 최대 {{ $maxCount }}개까지 선택할 수 있습니다.
        </flux:text>
    </div>
</div>
