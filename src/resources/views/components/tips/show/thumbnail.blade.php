@props(['tip'])
@php
    $thumbnailUrl = $tip->thumbnail?->publicUrl();
@endphp

@if ($thumbnailUrl)
    <figure class="tip-show__thumbnail">
        <img
            class="tip-show__thumbnail-image"
            src="{{ $thumbnailUrl }}"
            alt="{{ $tip->title }} 썸네일"
            loading="eager"
        >
    </figure>
@endif
