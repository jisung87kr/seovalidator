@props([
    'variant' => 'default',
    'size' => 'md',
])

@php
$baseClasses = 'inline-flex items-center font-medium rounded-full';

$variants = [
    'default' => 'bg-surface-subtle text-content-secondary',
    'primary' => 'bg-primary text-white',
    'accent' => 'bg-accent-light text-accent-dark',
    'success' => 'bg-success-light text-success-dark',
    'warning' => 'bg-warning-light text-warning-dark',
    'error' => 'bg-error-light text-error-dark',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-xs',
    'lg' => 'px-3 py-1.5 text-sm',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['default']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
