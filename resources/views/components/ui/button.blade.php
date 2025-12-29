@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed';

$variants = [
    'primary' => 'bg-primary text-white hover:bg-primary-hover focus:ring-primary',
    'secondary' => 'bg-white text-primary border border-border hover:bg-surface-subtle focus:ring-primary',
    'ghost' => 'text-content-secondary hover:text-primary hover:bg-surface-subtle focus:ring-primary',
    'accent' => 'bg-accent text-white hover:bg-accent-hover focus:ring-accent',
    'danger' => 'bg-error text-white hover:bg-error-dark focus:ring-error',
];

$sizes = [
    'sm' => 'px-4 py-2 text-sm',
    'md' => 'px-6 py-3 text-sm',
    'lg' => 'px-8 py-4 text-base',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled]) }}>
        {{ $slot }}
    </button>
@endif
