@props([
    'variant' => 'default',
    'hover' => false,
    'padding' => 'default',
])

@php
$baseClasses = 'rounded-2xl transition-all duration-200';

$variants = [
    'default' => 'bg-white border border-border shadow-card',
    'elevated' => 'bg-white border border-border shadow-soft',
    'subtle' => 'bg-surface-subtle border border-border-subtle',
    'outline' => 'bg-transparent border border-border',
];

$paddings = [
    'none' => '',
    'sm' => 'p-4',
    'default' => 'p-6 sm:p-8',
    'lg' => 'p-8 sm:p-10',
];

$hoverClasses = $hover ? 'hover:shadow-card-hover hover:-translate-y-0.5 cursor-pointer' : '';

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['default']) . ' ' . ($paddings[$padding] ?? $paddings['default']) . ' ' . $hoverClasses;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
