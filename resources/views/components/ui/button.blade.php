@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'public-primary',
    'block' => false,
    'size' => null,
])

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'btn',
            'common_btn' => $variant === 'public-primary',
            'common_btn_2' => $variant === 'public-secondary',
            'btn-primary' => in_array($variant, ['backend-primary', 'auth-primary'], true),
            'btn-sm' => $size === 'sm',
            'btn-lg' => $size === 'lg',
            'w-100' => $block,
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->class([
            'btn',
            'common_btn' => $variant === 'public-primary',
            'common_btn_2' => $variant === 'public-secondary',
            'btn-primary' => in_array($variant, ['backend-primary', 'auth-primary'], true),
            'btn-sm' => $size === 'sm',
            'btn-lg' => $size === 'lg',
            'w-100' => $block,
        ]) }}
    >
        {{ $slot }}
    </button>
@endif
