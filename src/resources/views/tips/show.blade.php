<x-layouts.front title="팁 상세">
    <section class="tip-show" data-tip-show>
        <div class="tip-show__topbar">
            <div></div>
            <button type="button" class="tip-show__icon-btn tip-show__mobile-only" aria-label="공유">
                공유
            </button>
        </div>

        <div class="tip-show__layout">
            <x-tips.show.toc />

            <article class="tip-show__article">
                <x-tips.show.header />
                <x-tips.show.content />
                <x-tips.show.tags />
                <x-tips.show.reactions />
                <x-tips.show.comments />
            </article>

            <x-tips.show.actions />
        </div>
    </section>
</x-layouts.front>
