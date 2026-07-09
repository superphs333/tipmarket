@props([
    'actions' => [],
])

<aside class="tip-show__action" aria-label="팁 액션">
    <x-actions.group
        variant="responsive"
        :actions="$actions"
        label="팁 액션"
    />
</aside>
