<!-- Tip 검색 필터 시작 -->
<section class="tip-search-card">
    <form
        class="tip-search-form"
        @if ($context === 'console')
            wire:submit.prevent="search"
        @else
            method="GET"
            action="{{ route('tips.search') }}"
        @endif
    >
        @if ($context === 'front' && request()->filled('sort'))
            <!-- 검색 조건을 다시 적용해도 현재 프론트 정렬값을 유지한다. -->
            <input
                type="hidden"
                name="sort"
                value="{{ request('sort') }}"
            >
        @endif

        <!-- 검색 조건 영역 시작 -->
        <div class="tip-search-fields">
            <!-- 카테고리 필터와 관리자 전용 필터 -->
            <div class="tip-search-top-row">
                <!-- 공통 카테고리 필터 -->
                <div class="tip-search-pair">
                    <label for="{{ $context }}-tip-filter-category" class="tip-search-label">카테고리</label>
                    <select
                        id="{{ $context }}-tip-filter-category"
                        class="tip-search-control"
                        @if ($context === 'console')
                            wire:model="categoryId"
                        @else
                            name="category"
                        @endif
                    >
                        <option value="">전체</option>
                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected($context === 'front' && (string) request('category') === (string) $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($context === 'console')
                    <!-- 관리자 전용 노출 필터 -->
                    <div class="tip-search-pair">
                        <label for="console-tip-filter-audience" class="tip-search-label">노출</label>
                        <select id="console-tip-filter-audience" wire:model="audience" class="tip-search-control">
                            <option value="">노출</option>
                            @foreach ($audienceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 관리자 전용 상태 필터 -->
                    <div class="tip-search-pair">
                        <label for="console-tip-filter-status" class="tip-search-label">상태</label>
                        <select id="console-tip-filter-status" wire:model="status" class="tip-search-control">
                            <option value="">상태</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- 공통 태그 필터 -->
            <div class="tip-search-row">
                <div class="tip-search-label">태그</div>
                <div class="tip-search-tag-selector">
                    @if ($context === 'console')
                        <livewire:tags.tag-selector
                            wire:model="tagNames"
                            label=""
                            placeholder="태그 이름 검색"
                            name="tag_names"
                            :allow-create="false"
                            variant="compact"
                        />
                    @else
                        <x-tags.selector
                            label=""
                            placeholder="태그 이름 검색"
                            name="tag_names"
                            :selected="(array) request()->input('tag_names', [])"
                            :allow-create="false"
                            variant="compact"
                        />
                    @endif
                </div>
            </div>

            @if ($context === 'console')
                <!-- 관리자 전용 기간 필터 -->
                <div class="tip-search-row">
                    <div class="tip-search-label">기간</div>
                    <div class="tip-search-range">
                        <input type="date" wire:model="createdFrom" class="tip-search-control">
                        <span class="tip-search-separator">~</span>
                        <input type="date" wire:model="createdTo" class="tip-search-control">
                    </div>
                </div>
            @endif

            <!-- 공통 검색어 필터 -->
            <div class="tip-search-row">
                <label for="{{ $context }}-tip-filter-keyword" class="tip-search-label">검색어</label>
                <input
                    id="{{ $context }}-tip-filter-keyword"
                    type="search"
                    placeholder="검색어 입력(제목/작성자)"
                    class="tip-search-control"
                    @if ($context === 'console')
                        wire:model="keyword"
                    @else
                        name="query"
                        value="{{ request('query') }}"
                    @endif
                >
            </div>
        </div>
        <!-- 검색 조건 영역 끝 -->

        <!-- 검색 액션 영역 시작 -->
        <div class="tip-search-actions">
            @if ($context === 'console')
                <flux:button type="button" variant="outline" wire:click="resetFilters">
                    초기화
                </flux:button>
            @else
                <flux:button :href="route('tips.search')" variant="outline">
                    초기화
                </flux:button>
            @endif

            <flux:button type="submit" variant="primary">
                검색
            </flux:button>
        </div>
        <!-- 검색 액션 영역 끝 -->
    </form>
</section>
<!-- Tip 검색 필터 끝 -->
