<x-layouts.front title="팁 수정">
    <section class="tip-edit-page">
        <div class="tip-edit-page__header">
            <div>
                <p class="tip-edit-page__eyebrow">Tip</p>
                <h1 class="tip-edit-page__title">팁 수정</h1>
            </div>

            <a href="{{ route('tips.show', $tip) }}" class="tip-edit-page__back">
                상세로
            </a>
        </div>

        <x-tips.edit-form
            :tip="$tip"
            mode="edit"
            :action="route('tips.update', $tip)"
            method="PUT"
            :cancel-url="route('tips.show', $tip)"
        />
    </section>
</x-layouts.front>
