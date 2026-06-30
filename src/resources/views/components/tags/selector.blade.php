@props([
    'label' => '태그',
    'placeholder' => '태그 이름 검색...',
    'maxCount' => null,
    'name' => 'tag_names',
    'selected' => [],
])

<div {{ $attributes }}>
    <livewire:tags.tag-selector
        :label="$label"
        :placeholder="$placeholder"
        :name="$name"
        :max-count="$maxCount"
        :selected="$selected"
    />
</div>
