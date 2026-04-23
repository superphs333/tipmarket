@props([
    'alt' => '',
])

<img
    src="{{ asset('images/logo.png') }}"
    alt="{{ $alt }}"
    width="2048"
    height="2048"
    {{ $attributes->merge(['class' => 'block object-contain']) }}
>
