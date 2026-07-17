<flux:modal
    name="global-search"
    variant="bare"
    :closable="false"
    focusable
    class="global-search-modal"
>
    <section class="front-search-modal" aria-labelledby="global-search-title">
        <header class="front-search-modal__header">
            <div>
                <h2 id="global-search-title" class="front-search-modal__title">검색</h2>
                <p class="front-search-modal__date">{{ now()->format('Y.m.d') }}</p>
            </div>

            <flux:modal.close>
                <button
                    type="button"
                    class="front-search-modal__close"
                    aria-label="검색 닫기"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </flux:modal.close>
        </header>

        <form 
            class="front-search-modal__form"
            method="GET"
            action="/tips/search"
            role="search"
            data-search-form
            >
            <label class="sr-only" for="global-search-query">검색어</label>
            <input
                id="global-search-query"
                class="front-search-modal__input"
                type="search"
                name="query"
                placeholder="검색어를 입력하세요"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                data-search-input
            >

            <button
                type="submit"
                class="front-search-modal__submit"
                aria-label="검색 실행"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16.5 16.5 4 4"></path>
                </svg>
            </button>
        </form>
    </section>
</flux:modal>
