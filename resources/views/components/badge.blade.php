@props([
    'color' => 'gray', // green, red, amber, blue, purple, orange, gray
    'size' => 'sm',
])

@php
    $colorClass = match($color) {
        'green', 'success', 'active', 'paid', 'completed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30',
        'red', 'danger', 'suspended', 'cancelled', 'out_of_stock' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-950/50 dark:text-red-400 dark:ring-red-500/30',
        'amber', 'warning', 'pending', 'unpaid', 'low_stock' => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-400 dark:ring-amber-500/30',
        'blue', 'info', 'confirmed', 'preparing' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-950/50 dark:text-blue-400 dark:ring-blue-500/30',
        'purple', 'ready' => 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-950/50 dark:text-purple-400 dark:ring-purple-500/30',
        'orange' => 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20 dark:bg-orange-950/50 dark:text-orange-400 dark:ring-orange-500/30',
        default => 'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600/30',
    };

    $sizeClass = match($size) {
        'xs' => 'px-1.5 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-sm',
        default => 'px-2 py-0.5 text-xs',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-bold rounded-lg {$colorClass} {$sizeClass}"]) }}>
    {{ $slot }}
</span>
