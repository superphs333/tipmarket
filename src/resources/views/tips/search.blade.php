
<x-layouts.front title="팁 검색">
    <!-- 검색 페이지 본문 시작 -->
    <section class="tip-search-page">
        <!-- 검색 페이지 헤더 시작 -->
        <header class="tip-search-page__header">
            <!-- 검색 페이지 보조 제목 -->
            <p class="tip-search-page__eyebrow">
                TIP SEARCH
            </p>

            <!-- 검색 페이지 제목 -->
            <h1 class="tip-search-page__title">
                팁 검색
            </h1>

            <!-- 검색 페이지 설명 -->
            <p class="tip-search-page__description">
                필요한 생활 팁을 검색해 보세요.
            </p>
        </header>
        <!-- 검색 페이지 헤더 끝 -->

        <!-- 검색 조건 영역 시작 -->
        <x-tips.search.filter-form />
        <!-- 검색 조건 영역 끝 -->

        <!-- 검색 결과 영역 시작 -->
        <x-tips.search.results />
        <!-- 검색 결과 영역 끝 -->
    </section>
    <!-- 검색 페이지 본문 끝 -->
</x-layouts.front>