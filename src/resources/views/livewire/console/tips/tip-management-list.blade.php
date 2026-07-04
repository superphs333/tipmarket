<div class="space-y-6">
    {{--
        [검색/필터 패널]
        관리자 팁관리 목록을 조건별로 좁혀 보는 운영 도구 영역이다.

        설계 의도:
        - 상단에는 선택형 필터를 모아 한눈에 스캔하게 한다.
        - 태그 선택기는 아직 작성 폼용 기본 UI이므로 별도 행으로 배치해 높이 충돌을 피한다.
        - 기간/검색어/액션은 마지막 행에 배치해 실제 조회 실행 흐름을 자연스럽게 만든다.

        데이터 흐름:
        - 이 Blade는 wire:model으로 Livewire 상태만 갱신한다.
        - TipManagementList는 상태를 tipListFilters() 배열로 변환한다.
        - TipListQuery가 필터 정규화와 DB 조건 조립을 담당한다.
    --}}
    <section class="tip-search-card">
        {{--
            [검색 폼]
            필터 전용 CSS로 행 간격과 필드 폭을 고정한다.
            버튼 컬럼은 입력 영역과 분리해 캡쳐처럼 오른쪽에 세로로 배치한다.
        --}}
        <div class="tip-search-form">
            <div class="tip-search-fields">
                {{--
                    [1행: 카테고리/노출/상태]
                    각 항목은 라벨과 입력이 한 묶음으로 움직인다.
                --}}
                <div class="tip-search-top-row">
                    <div class="tip-search-pair">
                        <label for="tip-filter-category" class="tip-search-label">카테고리</label>
                        <select id="tip-filter-category" wire:model="categoryId" class="tip-search-control">
                            <option value="">전체</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tip-search-pair">
                        <label for="tip-filter-audience" class="tip-search-label">노출</label>
                        <select id="tip-filter-audience" wire:model="audience" class="tip-search-control">
                            <option value="">노출</option>
                            @foreach ($audienceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tip-search-pair">
                        <label for="tip-filter-status" class="tip-search-label">상태</label>
                        <select id="tip-filter-status" wire:model="status" class="tip-search-control">
                            <option value="">상태</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{--
                    [2행: 태그]
                    선택된 태그 영역은 비어 있어도 항상 보여 현재 조건을 명확히 한다.
                --}}
                <div class="tip-search-row">
                    <div class="tip-search-label">태그</div>
                    <div class="tip-search-tag-selector">
                        <livewire:tags.tag-selector
                            wire:model="tagNames"
                            label=""
                            placeholder="태그 이름 검색"
                            name="tag_names"
                            :allow-create="false"
                            variant="compact"
                        />
                    </div>
                </div>

                {{--
                    [3행: 기간]
                    시작일과 종료일을 하나의 기간 조건으로 묶는다.
                --}}
                <div class="tip-search-row">
                    <div class="tip-search-label">기간</div>
                    <div class="tip-search-range">
                        <input type="date" wire:model="createdFrom" class="tip-search-control">
                        <span class="tip-search-separator">~</span>
                        <input type="date" wire:model="createdTo" class="tip-search-control">
                    </div>
                </div>

                {{--
                    [4행: 검색어]
                    현재 검색 대상은 제목, 작성자명, 작성자 email이다.
                --}}
                <div class="tip-search-row">
                    <label for="tip-filter-keyword" class="tip-search-label">검색어</label>
                    <input
                        id="tip-filter-keyword"
                        type="search"
                        wire:model="keyword"
                        wire:keydown.enter.prevent="search"
                        placeholder="검색어 입력(제목/작성자)"
                        class="tip-search-control"
                    >
                </div>
            </div>

            {{--
                [액션 버튼]
                초기화와 검색 버튼은 오른쪽 고정 컬럼에서 세로로 배치한다.
            --}}
            <div class="tip-search-actions">
                <flux:button type="button" variant="outline" wire:click="resetFilters">
                    초기화
                </flux:button>
                <flux:button type="button" variant="primary" wire:click="search">
                    검색
                </flux:button>
            </div>
        </div>
    </section>

    {{--
        [검색 결과 테이블]
        운영자가 팁 상태를 빠르게 훑어보는 영역이다.
        컬럼이 많으므로 작은 화면에서는 가로 스크롤을 허용한다.
    --}}
    <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        {{--
            [테이블 상단 바]
            목록 성격과 현재 페이지 정보를 표시한다.
            실제 페이지 이동 UI는 하단 links()에 둔다.
        --}}
        <div class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">팁 목록</div>
                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ number_format($tips->firstItem() ?? 0) }}-{{ number_format($tips->lastItem() ?? 0) }} / {{ number_format($tips->total()) }}
                </div>
            </div>
        </div>

        {{--
        [팁 벌크 액션 바]

        type="tip":
        - 공통 템플릿 내부에서 팁 전용 액션 구역을 보여준다.
        - 현재 표시되는 액션은 상태 변경, 노출 변경, 삭제다.

        selected-count:
        - ManagesBulkSelection trait의 selectedIds 개수를 넘긴다.
        - 0이면 액션 바가 숨겨진다.

        status-options:
        - 팁 상태 변경 select에 사용할 옵션이다.

        audience-options:
        - 팁 노출 변경 select에 사용할 옵션이다.
        --}}
        <x-console.bulk-action-bar
            type="tip"
            :selected-count="count($selectedIds)"
            :bulk-action="$bulkAction"
            :bulk-value="$bulkValue"
            :status-options="$statusOptions"
            :audience-options="$audienceOptions"
        />

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                {{--
                    [테이블 헤더]
                    관리자 목록에 필요한 핵심 운영 정보를 컬럼으로 고정한다.
                --}}
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3">
                            <input type="checkbox"
                                wire:key="console-tip-select-all-{{ $this->selectAll ? 'checked' : 'unchecked' }}"
                                wire:click="toggleSelectAll"
                                @checked($this->selectAll)
                            />
                        </th>
                        <th class="px-5 py-3">제목</th>
                        <th class="px-4 py-3">작성자</th>
                        <th class="px-4 py-3">카테고리</th>
                        <th class="px-4 py-3">태그</th>
                        <th class="px-4 py-3">상태</th>
                        <th class="px-4 py-3">노출</th>
                        <th class="px-4 py-3 text-right">반응</th>
                        <th class="px-5 py-3">수정일</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($tips as $tip)
                        {{--
                            [팁 행]
                            wire:key는 Livewire DOM diff 안정성을 위해 팁 id를 사용한다.
                        --}}
                        <tr wire:key="console-tip-row-{{ $tip->id }}" class="transition hover:bg-zinc-50/80 dark:hover:bg-zinc-800/60">
                            <td  class="px-5 py-4">
                                <input type="checkbox"
                                    wire:model.live="selectedIds"
                                    value="{{ $tip->id }}"
                                />
                            </td>
                            {{--
                                [제목]
                                긴 제목은 말줄임 처리하고, 보조 식별자와 행 단위 보조 액션을 표시한다.
                            --}}
                            <td class="px-5 py-4">
                                <div class="max-w-md">
                                    <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $tip->title }}
                                    </div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        #{{ $tip->id }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-xs">
                                        @if (\Illuminate\Support\Facades\Route::has('console.tips.edit'))
                                            <a
                                                href="{{ route('console.tips.edit', $tip) }}"
                                                wire:navigate
                                                class="font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100"
                                            >
                                                수정
                                            </a>
                                        @else
                                            <span
                                                title="수정 화면은 아직 준비 중입니다."
                                                class="font-medium text-zinc-500 dark:text-zinc-400"
                                            >
                                                수정
                                            </span>
                                        @endif

                                        <span class="text-zinc-300 dark:text-zinc-700">|</span>

                                        @if (\Illuminate\Support\Facades\Route::has('console.tips.show'))
                                            <a
                                                href="{{ route('console.tips.show', $tip) }}"
                                                wire:navigate
                                                class="font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100"
                                            >
                                                본문이동
                                            </a>
                                        @else
                                            <span
                                                title="본문 화면은 아직 준비 중입니다."
                                                class="font-medium text-zinc-500 dark:text-zinc-400"
                                            >
                                                본문이동
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{--
                                [작성자]
                                user 관계는 TipListQuery에서 eager loading한다.
                            --}}
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <div class="max-w-44">
                                    <div class="truncate font-medium text-zinc-700 dark:text-zinc-200">{{ $tip->user?->name ?? '없음' }}</div>
                                    <div class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $tip->user?->email ?? '-' }}
                                    </div>
                                </div>
                            </td>

                            {{--
                                [카테고리]
                                카테고리 미지정 팁은 '-'로 표시한다.
                            --}}
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ $tip->category?->name ?? '-' }}
                            </td>

                            {{--
                                [태그]
                                연결 태그는 배경 없는 해시태그 텍스트로 표시한다.
                            --}}
                            <td class="px-4 py-4">
                                <div class="flex max-w-60 flex-wrap gap-x-4 gap-y-2">
                                    @forelse ($tip->tags as $tag)
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                            #{{ $tag->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">-</span>
                                    @endforelse
                                </div>
                            </td>

                            {{--
                                [상태]
                                상태 값은 라벨과 색상으로 구분한다.
                            --}}
                            <td class="px-4 py-4">
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-400/20 dark:text-amber-200' => $tip->status === \App\Models\Tip::STATUS_DRAFT,
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/20 dark:text-emerald-200' => $tip->status === \App\Models\Tip::STATUS_PUBLISHED,
                                    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => ! in_array($tip->status, [\App\Models\Tip::STATUS_DRAFT, \App\Models\Tip::STATUS_PUBLISHED], true),
                                ])>
                                    {{ $statusOptions[$tip->status] ?? $tip->status }}
                                </span>
                            </td>

                            {{--
                                [노출]
                                public/premium/private 값을 라벨과 색상으로 구분한다.
                            --}}
                            <td class="px-4 py-4">
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/20 dark:text-emerald-200' => $tip->audience === \App\Models\Tip::AUDIENCE_PUBLIC,
                                    'bg-violet-100 text-violet-800 dark:bg-violet-400/20 dark:text-violet-200' => $tip->audience === \App\Models\Tip::AUDIENCE_PREMIUM,
                                    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $tip->audience === \App\Models\Tip::AUDIENCE_PRIVATE,
                                ])>
                                    {{ $audienceOptions[$tip->audience] ?? $tip->audience }}
                                </span>
                            </td>

                            {{--
                                [반응]
                                현재는 조회수와 좋아요 수를 표시한다.
                            --}}
                            <td class="px-4 py-4 text-right text-xs text-zinc-500 dark:text-zinc-400">
                                <div class="whitespace-nowrap">
                                    <span>조회 {{ number_format($tip->view_count) }}</span>
                                    <span class="mx-1 text-zinc-300 dark:text-zinc-600">/</span>
                                    <span>좋아요 {{ number_format($tip->like_count) }}</span>
                                </div>
                            </td>

                            {{--
                                [수정일]
                                운영자가 최근 변경 여부를 확인할 때 사용한다.
                            --}}
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                                {{ $tip->updated_at?->toDateString() }}
                            </td>
                        </tr>
                    @empty
                        {{--
                            [빈 상태]
                            검색 조건에 맞는 결과가 없을 때 표시한다.
                        --}}
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                조건에 맞는 팁이 없습니다.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{--
            [페이지네이션]
            LengthAwarePaginator의 기본 links()를 사용한다.
        --}}
        <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-700">
            {{ $tips->links() }}
        </div>
    </section>
</div>
