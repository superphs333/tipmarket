<x-layouts.front title="팁 작성">
    <section class="tip-edit-page">
        <div class="tip-edit-page__header">
            <div>
                <p class="tip-edit-page__eyebrow">Tip</p>
                <h1 class="tip-edit-page__title">팁 작성</h1>
            </div>

            <a href="{{ route('home') }}" class="tip-edit-page__back">
                홈으로
            </a>
        </div>

        <x-tips.edit-form
            :tip="$tip"
            mode="create"
            :action="route('tips.store')"
            method="POST"
            :cancel-url="route('home')"
        />
    </section>
</x-layouts.front>
