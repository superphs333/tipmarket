@props([
    // 백엔드 연결 전까지 부모 화면이 전달하는 실제 결과는 사용하지 않는다.
    'tips' => null,
])

@php
    // 프론트 검색 결과 UI 확인용 임시 데이터입니다.
    // 백엔드 연결 단계에서 실제 검색 결과 데이터로 교체합니다.
    $previewTips = [
        [
            'title' => '욕실 물때를 10분 안에 정리하는 청소 순서',
            'category' => '청소',
            'author' => '정리생활연구소',
            'author_initial' => '정',
            'date' => '2026.07.19',
            'summary' => '세면대와 수전의 물때를 순서대로 제거하고 물자국을 줄이는 청소 방법입니다.',
            'tags' => ['욕실', '식초', '초보자'],
            'comment_count' => 8,
            'like_count' => 32,
            'bookmark_count' => 14,
            'thumbnail_url' => null,
        ],
        [
            'title' => '전자레인지 찌든때를 스팀으로 청소하는 방법',
            'category' => '청소',
            'author' => '주방실험실',
            'author_initial' => '주',
            'date' => '2026.07.18',
            'summary' => '물컵에서 발생한 수증기로 내부 오염을 불린 뒤 간단하게 닦아내는 방법입니다.',
            'tags' => ['전자레인지', '냄새제거'],
            'comment_count' => 5,
            'like_count' => 24,
            'bookmark_count' => 11,
            'thumbnail_url' => null,
        ],
        [
            'title' => '청소도구 보관 위치만 바꿔도 쉬워지는 정리법',
            'category' => '생활',
            'author' => '살림한스푼',
            'author_initial' => '살',
            'date' => '2026.07.17',
            'summary' => '사용 장소 가까이에 청소도구를 나눠 배치해 청소 시작 장벽을 낮추는 방법입니다.',
            'tags' => ['정리정돈', '청소도구', '수납'],
            'comment_count' => 12,
            'like_count' => 41,
            'bookmark_count' => 19,
            'thumbnail_url' => null,
        ],
    ];
@endphp

<!-- Tip 검색 결과 시작 -->
<section class="tip-search-results" aria-labelledby="tip-search-results-title">
    <!-- 검색 결과 헤더 시작 -->
    <header class="tip-search-results__header">
        <div>
            <p class="tip-search-results__eyebrow">SEARCH RESULTS</p>
            <h2 id="tip-search-results-title" class="tip-search-results__title">검색 결과</h2>
        </div>

        <p class="tip-search-results__count">
            {{ count($previewTips) }}개의 게시글
        </p>
    </header>
    <!-- 검색 결과 헤더 끝 -->

    <!-- 검색 결과 목록 시작 -->
    <div class="tip-search-results__list">
        @forelse ($previewTips as $tip)
            <!-- 검색 결과 항목 시작 -->
            <article class="tip-search-result-card">
                <!-- Tip 썸네일 -->
                <a
                    href="#"
                    class="tip-search-result-card__thumbnail"
                    aria-label="{{ $tip['title'] }} 상세 보기"
                >
                    @if ($tip['thumbnail_url'])
                        <img src="{{ $tip['thumbnail_url'] }}" alt="{{ $tip['title'] }}" loading="lazy">
                    @else
                        <span class="tip-search-result-card__thumbnail-placeholder" aria-hidden="true">
                            TIP
                        </span>
                    @endif
                </a>

                <!-- Tip 정보 영역 시작 -->
                <div class="tip-search-result-card__body">
                    <span class="tip-search-result-card__category">
                        {{ $tip['category'] }}
                    </span>

                    <h3 class="tip-search-result-card__title">
                        <a href="#">{{ $tip['title'] }}</a>
                    </h3>

                    <!-- 작성자와 게시 정보 -->
                    <div class="tip-search-result-card__meta">
                        <span class="tip-search-result-card__author">
                            <span class="tip-search-result-card__avatar" aria-hidden="true">
                                {{ $tip['author_initial'] }}
                            </span>
                            <span>{{ $tip['author'] }}</span>
                        </span>
                        <span>댓글 {{ number_format($tip['comment_count']) }}</span>
                        <time datetime="{{ str_replace('.', '-', $tip['date']) }}">
                            {{ $tip['date'] }}
                        </time>
                    </div>

                    <p class="tip-search-result-card__summary">
                        {{ $tip['summary'] }}
                    </p>

                    <!-- 태그와 반응 정보 -->
                    <div class="tip-search-result-card__footer">
                        <div class="tip-search-result-card__tags" aria-label="게시글 태그">
                            @foreach ($tip['tags'] as $tag)
                                <span class="tip-search-result-card__tag">#{{ $tag }}</span>
                            @endforeach
                        </div>

                        <div class="tip-search-result-card__engagement" aria-label="게시글 반응 수">
                            <span title="좋아요">
                                <flux:icon.heart class="size-4" />
                                {{ number_format($tip['like_count']) }}
                            </span>
                            <span title="북마크">
                                <flux:icon.bookmark class="size-4" />
                                {{ number_format($tip['bookmark_count']) }}
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
</section>
<!-- Tip 검색 결과 끝 -->
