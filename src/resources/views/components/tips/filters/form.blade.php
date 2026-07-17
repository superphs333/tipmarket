@if ($context === 'console')
    <!-- 관리자 Tip 검색 필터 시작 -->
    <section class="tip-search-card">
        <div class="tip-search-form">
            <!-- 검색 조건 영역 시작 -->
            <div class="tip-search-fields">
                <!-- 카테고리, 노출, 상태 필터 -->
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

                <!-- 태그 필터 -->
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

                <!-- 기간 필터 -->
                <div class="tip-search-row">
                    <div class="tip-search-label">기간</div>
                    <div class="tip-search-range">
                        <input type="date" wire:model="createdFrom" class="tip-search-control">
                        <span class="tip-search-separator">~</span>
                        <input type="date" wire:model="createdTo" class="tip-search-control">
                    </div>
                </div>

                <!-- 검색어 필터 -->
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
            <!-- 검색 조건 영역 끝 -->

            <!-- 검색 액션 영역 -->
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
    <!-- 관리자 Tip 검색 필터 끝 -->
@endif
