@php
    $navLinks = [
        [
            'area' => 'overview',
            'route' => 'business.ai.overview',
            'active' => request()->routeIs('business.ai.overview'),
            'label' => 'AI Overview',
        ],
        [
            'area' => 'assistant',
            'route' => 'business.ai.assistant',
            'active' => request()->routeIs('business.ai.assistant*'),
            'label' => 'Business Assistant',
        ],
        [
            'area' => 'menu_creator',
            'route' => 'business.ai.menu-creator',
            'active' => request()->routeIs('business.ai.menu-creator*'),
            'label' => 'Menu Creator',
        ],
        [
            'area' => 'marketing_studio',
            'route' => 'business.ai.marketing',
            'active' => request()->routeIs('business.ai.marketing*'),
            'label' => 'Marketing Studio',
        ],
        [
            'area' => 'inventory_forecast',
            'route' => 'business.ai.inventory-forecast',
            'active' => request()->routeIs('business.ai.inventory-forecast*'),
            'label' => 'Inventory Forecast',
        ],
        [
            'area' => 'sales_prediction',
            'route' => 'business.ai.sales-prediction',
            'active' => request()->routeIs('business.ai.sales-prediction*'),
            'label' => 'Sales Prediction',
        ],
        [
            'area' => 'usage',
            'route' => 'business.ai.usage',
            'active' => request()->routeIs('business.ai.usage*'),
            'label' => 'Usage & History',
        ],
        [
            'area' => 'settings',
            'route' => 'business.ai.settings',
            'active' => request()->routeIs('business.ai.settings*'),
            'label' => 'AI Settings',
        ],
    ];
@endphp

<div class="rounded-2xl bg-[#0F1E3A] text-white p-4 sm:p-5 shadow-sm border border-[#1E3560]">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-400/40 flex items-center justify-center text-amber-300 font-black text-base shrink-0">
                ✨
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold tracking-tight text-white">SABAYRA AI Center</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                        {{ strtoupper($platformConfig['default_provider'] ?? 'gemini') }} · {{ $platformConfig['default_model'] ?? 'gemini-2.5-flash' }}
                    </span>
                </div>
                <p class="text-xs text-slate-300 mt-0.5">
                    Smarter Business, Simpler Every Day · Grounded strictly in your store's live data
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            <div class="px-3 py-1.5 rounded-xl bg-white/10 border border-white/15">
                <span class="text-slate-300">Credits Left:</span>
                <span class="font-extrabold text-amber-300 ml-1">{{ number_format($monthlySummary['remaining_credits'] ?? 0) }}</span>
                <span class="text-slate-400">/ {{ number_format($monthlySummary['total_credits'] ?? 0) }}</span>
            </div>
            <div class="px-3 py-1.5 rounded-xl bg-white/10 border border-white/15">
                <span class="text-slate-300">Plan:</span>
                <span class="font-bold text-white ml-1">{{ $entitlements['plan_name'] ?? 'Launch' }}</span>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-3 border-t border-white/10 flex flex-wrap gap-1.5">
        @foreach($navLinks as $item)
            @if($allowedAreas[$item['area']] ?? false)
                <a href="{{ route($item['route']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $item['active'] ? 'bg-orange-500 text-white shadow-xs' : 'bg-white/5 text-slate-200 hover:bg-white/15 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
