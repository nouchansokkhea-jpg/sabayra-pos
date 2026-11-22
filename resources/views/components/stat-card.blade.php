@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'orange',
    'trend' => null,
    'trendUp' => true,
])

@php
    $colorClasses = match($color) {
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        'cyan' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
        'green' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
        'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
        'red' => 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        default => 'bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    };
@endphp

<div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 transition hover:shadow-md">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-bold tracking-wider text-slate-600 uppercase dark:text-slate-300">{{ $title }}</p>
            <h3 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $value }}</h3>
            @if($subtitle)
                <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $subtitle }}</p>
            @endif
        </div>
        @if($icon)
            <div class="flex items-center justify-center w-12 h-12 rounded-xl {{ $colorClasses }}">
                {!! $icon !!}
            </div>
        @endif
    </div>

    @if($trend !== null)
        <div class="flex items-center mt-4 text-xs font-medium {{ $trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
            <svg class="w-4 h-4 mr-1 {{ $trendUp ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            <span>{{ $trend }}</span>
            <span class="ml-1 text-gray-400 dark:text-gray-500">vs last month</span>
        </div>
    @endif
</div>
