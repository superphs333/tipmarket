@props([
    'tips',
])

<!-- Tip 검색 결과 시작 -->
<section class="tip-search-results" aria-labelledby="tip-search-results-title">
    <!-- 검색 결과 헤더 시작 -->
    <header class="tip-search-results__header">
        <!-- 검색 결과 제목 영역 -->
        <div>
            <p class="tip-search-results__eyebrow">
                SEARCH RESULTS
            </p>

            <h2
                id="tip-search-results-title"
                class="tip-search-results__title"
            >
                검색 결과
            </h2>
        </div>

        <!-- 검색 결과 정보와 정렬 영역 -->
        <div class="tip-search-results__tools">
            <!-- 현재 검색 결과 개수 -->
            <p class="tip-search-results__count">
                {{ number_format($tips->total()) }}개의 게시글
            </p>

            <!-- 프론트 검색 결과 정렬 폼 -->
            <form
                method="GET"
                action="{{ route('tips.search') }}"
                class="tip-search-results__sort-form"
                data-search-sort-form
            >
                {{--
                    정렬값을 변경해도 기존 검색어와 필터가 유지되도록
                    현재 검색 조건을 hidden input으로 다시 전달한다.
                --}}
                @if (request()->filled('query'))
                    <input
                        type="hidden"
                        name="query"
                        value="{{ request('query') }}"
                    >
                @endif

                @if (request()->filled('category'))
                    <input
                        type="hidden"
                        name="category"
                        value="{{ request('category') }}"
                    >
                @endif

                @foreach ((array) request()->input('tag_names', []) as $tagName)
                    <input
                        type="hidden"
                        name="tag_names[]"
                        value="{{ $tagName }}"
                    >
                @endforeach

                <label
                    for="front-tip-sort"
                    class="tip-search-results__sort-label"
                >
                    정렬
                </label>

                <select
                    id="front-tip-sort"
                    name="sort"
                    class="tip-search-results__sort-select"
                    data-search-sort-select
                >
                    <option
                        value="latest"
                        @selected(request('sort', 'latest') === 'latest')
                    >
                        최신순
                    </option>

                    <option
                        value="popular"
                        @selected(request('sort') === 'popular')
                    >
                        조회순
                    </option>

                    <option
                        value="likes"
                        @selected(request('sort') === 'likes')
                    >
                        좋아요순
                    </option>

                    <option
                        value="bookmarks"
                        @selected(request('sort') === 'bookmarks')
                    >
                        북마크순
                    </option>
                </select>

                <!-- JavaScript를 사용할 수 없는 환경의 정렬 실행 버튼 -->
                <noscript>
                    <button
                        type="submit"
                        class="tip-search-results__sort-submit"
                    >
                        적용
                    </button>
                </noscript>
            </form>
        </div>
    </header>
    <!-- 검색 결과 헤더 끝 -->

    <!-- 검색 결과 목록 시작 -->
    <div class="tip-search-results__list">
        @forelse ($tips as $tip)
            @php
                // 검색 카드에서 반복 사용하는 표시값만 View에서 간단히 가공한다.
                $tipUrl = route('tips.show', $tip);
                $thumbnailUrl = $tip->thumbnail?->publicUrl();
                $authorName = $tip->user?->name ?? '작성자';
                $authorInitial = Illuminate\Support\Str::substr($authorName, 0, 1);
                $summary = (string) Illuminate\Support\Str::of($tip->content)
                    ->stripTags()
                    ->squish()
                    ->limit(160);
            @endphp

            <!-- 검색 결과 항목 시작 -->
            <article class="tip-search-result-card">
                <!-- Tip 썸네일 -->
                <a
                    href="{{ $tipUrl }}"
                    class="tip-search-result-card__thumbnail"
                    aria-label="{{ $tip->title }} 상세 보기"
                >
                    @if ($thumbnailUrl)
                        <img
                            src="{{ $thumbnailUrl }}"
                            alt="{{ $tip->title }} 썸네일"
                            loading="lazy"
                        >
                    @else
                        <span class="tip-search-result-card__thumbnail-placeholder" aria-hidden="true">
                            TIP
                        </span>
                    @endif
                </a>

                <!-- Tip 정보 영역 시작 -->
                <div class="tip-search-result-card__body">
                    <span class="tip-search-result-card__category">
                        {{ $tip->category?->name ?? '미분류' }}
                    </span>

                    <h3 class="tip-search-result-card__title">
                        <a href="{{ $tipUrl }}">{{ $tip->title }}</a>
                    </h3>

                    <!-- 작성자와 게시 정보 -->
                    <div class="tip-search-result-card__meta">
                        <span class="tip-search-result-card__author">
                            <span class="tip-search-result-card__avatar" aria-hidden="true">
                                {{ $authorInitial }}
                            </span>
                            <span>{{ $authorName }}</span>
                        </span>
                        <span>댓글 {{ number_format($tip->comment_count) }}</span>
                        <time datetime="{{ $tip->updated_at?->toDateString() }}">
                            {{ $tip->updated_at?->format('Y.m.d') }}
                        </time>
                    </div>

                    <p class="tip-search-result-card__summary">
                        {{ $summary !== '' ? $summary : '내용 미리보기가 없습니다.' }}
                    </p>

                    <!-- 태그와 반응 정보 -->
                    <div class="tip-search-result-card__footer">
                        <div class="tip-search-result-card__tags" aria-label="게시글 태그">
                            @forelse ($tip->tags as $tag)
                                <span class="tip-search-result-card__tag">#{{ $tag->name }}</span>
                            @empty
                                <span class="tip-search-result-card__tag">태그 없음</span>
                            @endforelse
                        </div>

                        <div class="tip-search-result-card__engagement" aria-label="게시글 반응 수">
                            <span title="좋아요">
                                <flux:icon.heart class="size-4" />
                                {{ number_format($tip->like_count) }}
                            </span>
                            <span title="북마크">
                                <flux:icon.bookmark class="size-4" />
                                {{ number_format($tip->bookmark_count) }}
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Tip 정보 영역 끝 -->
            </article>
            <!-- 검색 결과 항목 끝 -->
        @empty
            <!-- 검색 결과 빈 상태 시작 -->
            <div class="tip-search-results__empty">
                <p class="tip-search-results__empty-title">검색 결과가 없습니다.</p>
                <p class="tip-search-results__empty-description">
                    검색어를 변경하거나 다른 카테고리와 태그를 선택해 보세요.
                </p>
            </div>
            <!-- 검색 결과 빈 상태 끝 -->
        @endforelse
    </div>
    <!-- 검색 결과 목록 끝 -->

    @if ($tips->hasPages())
        <!-- 검색 결과 페이지네이션 시작 -->
        <nav class="tip-search-results__pagination" aria-label="검색 결과 페이지 이동">
            {{ $tips->onEachSide(1)->links() }}
        </nav>
        <!-- 검색 결과 페이지네이션 끝 -->
    @endif
</section>
<!-- Tip 검색 결과 끝 -->
