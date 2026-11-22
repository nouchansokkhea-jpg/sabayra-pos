@props([
    'usd',
    'rate' => null,
    'size' => 'base', // sm, base, lg, xl
    'showKhr' => true,
])

@php
    $exchangeRate = $rate ?? (isset($currentBusiness) ? (float)$currentBusiness->exchange_rate_khr : (float)config('menuhub.default_exchange_rate_khr', 4100));
    $khr = round((float)$usd * $exchangeRate, -2);

    $sizeClasses = match($size) {
        'sm' => 'text-sm font-semibold',
        'lg' => 'text-lg font-bold',
        'xl' => 'text-2xl font-black',
        default => 'text-base font-bold',
    };
@endphp

<span class="inline-flex items-baseline space-x-1.5">
    <span class="{{ $sizeClasses }} text-slate-900 dark:text-white font-mono">
        ${{ number_format((float)$usd, 2) }}
    </span>
    @if($showKhr)
        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 font-mono">
            ({{ number_format($khr, 0) }} ៛)
        </span>
    @endif
</span>
