@props([
    'id' => null,
    'name' => 'password',
    'label',
    'placeholder' => null,
    'autocomplete' => null,
    'icon' => 'form-pass',
    'error' => null,
    'maxlength' => null,
    'minlength' => null,
])

<div {{ $attributes->class(['form-group', $icon, 'password-toggle-field']) }}>
    <label for="{{ $id ?? $name }}">{{ $label }}</label>
    <input
        id="{{ $id ?? $name }}"
        type="password"
        name="{{ $name }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @if ($minlength) minlength="{{ $minlength }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id ?? $name }}-error" @endif
    >
    <button
        type="button"
        class="password-toggle-button"
        data-password-toggle-target="{{ $id ?? $name }}"
        data-show-label="{{ get_phrase('Show password') }}"
        data-hide-label="{{ get_phrase('Hide password') }}"
        aria-label="{{ get_phrase('Show password') }}"
        aria-pressed="false"
    >
        <i class="fas fa-eye" aria-hidden="true"></i>
    </button>
</div>

@if ($error)
    <p id="{{ $id ?? $name }}-error" class="text-danger" aria-live="polite">{{ $error }}</p>
@endif
