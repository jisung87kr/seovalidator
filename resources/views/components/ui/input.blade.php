@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'hint' => null,
    'icon' => null,
    'size' => 'md',
])

@php
$baseClasses = 'w-full bg-white border border-border rounded-xl text-content placeholder-content-muted transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent';

$sizes = [
    'sm' => 'px-4 py-2 text-sm',
    'md' => 'px-4 py-3 text-base',
    'lg' => 'px-6 py-4 text-lg',
];

$errorClasses = $error ? 'border-error focus:ring-error' : '';
$iconClasses = $icon ? 'pl-12' : '';

$inputClasses = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . $errorClasses . ' ' . $iconClasses;
@endphp

<div class="w-full">
    @if($label)
        <label class="block text-sm font-medium text-content mb-2">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-content-muted">
                {{ $icon }}
            </div>
        @endif

        <input
            type="{{ $type }}"
            {{ $attributes->merge(['class' => $inputClasses]) }}
        />
    </div>

    @if($error)
        <p class="mt-2 text-sm text-error">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-2 text-sm text-content-muted">{{ $hint }}</p>
    @endif
</div>
