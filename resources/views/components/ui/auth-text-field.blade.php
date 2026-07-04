@props([
    'id' => null,
    'name',
    'type' => 'text',
    'label',
    'value' => null,
    'placeholder' => null,
    'icon' => 'form-name',
    'error' => null,
    'autocomplete' => null,
    'autocapitalize' => null,
    'inputmode' => null,
    'maxlength' => null,
])

<div {{ $attributes->class(['form-group', $icon]) }}>
    <label for="{{ $id ?? $name }}">{{ $label }}</label>
    <input
        id="{{ $id ?? $name }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($autocapitalize) autocapitalize="{{ $autocapitalize }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id ?? $name }}-error" @endif
    >
</div>

@if ($error)
    <p id="{{ $id ?? $name }}-error" class="text-danger" aria-live="polite">{{ $error }}</p>
@endif
