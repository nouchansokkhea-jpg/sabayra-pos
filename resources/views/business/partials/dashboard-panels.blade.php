<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @if(in_array('revenue_chart', $activePanels, true))
        <div class="lg:col-span-2 p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-white">Revenue Trend (Last 7 Days)</h3>
                    <p class="text-xs text-slate-300 font-medium">Daily paid checkout revenue in USD</p>
                </div>
                <a href="{{ route('business.analytics.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">
                    Deep Analytics &rarr;
                </a>
            </div>
            <div class="h-64">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    @endif

    <!-- Security & Health Overview -->
    <div class="{{ in_array('revenue_chart', $activePanels, true) ? '' : 'lg:col-span-3' }} p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs flex flex-col justify-between">
        <div>
            <h3 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Security &amp; Workspace</span>
            </h3>
            <p class="text-xs text-slate-300 font-medium mb-4">{{ $categoryMeta['display_name'] }} isolation &amp; account health</p>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                    <span class="text-slate-200 font-semibold">2FA Protection</span>
                    @if ($securityOverview['two_factor_enabled'])
                        <span class="font-bold text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Enabled</span>
                    @else
                        <span class="font-bold text-xs px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">Disabled</span>
                    @endif
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                    <span class="text-slate-200 font-semibold">Last Owner Login</span>
                    <span class="font-bold text-white">{{ $securityOverview['last_login'] }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                    <span class="text-slate-200 font-semibold">Tenant Isolation</span>
                    <span class="font-bold text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Enforced</span>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-700/70 mt-4">
            <a href="{{ route('business.settings.index', ['section' => 'experience']) }}" class="text-xs font-bold text-orange-400 hover:text-orange-300 flex items-center justify-between transition">
                <span>Configure Category &amp; Modules</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
</div>

<!-- Dynamic Industry & Operational Panels -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @if(in_array('upcoming_appointments_panel', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">Upcoming Appointments</h3>
                <a href="{{ route('business.reservations.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">Open Schedule &rarr;</a>
            </div>
            @if ($upcomingAppointments->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300">
                    No upcoming service bookings yet. Share your online booking page to accept appointments 24/7.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach ($upcomingAppointments as $booking)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $booking->customer_name }} · {{ $booking->serviceProduct?->name_en ?? 'Service Booking' }}</h4>
                                <p class="text-[11px] text-slate-300">
                                    {{ $booking->reservation_date?->format('M d') }} at {{ $booking->reservation_time }}
                                    @if($booking->staffMember?->user?->name)
                                        · Staff: {{ $booking->staffMember->user->name }}
                                    @endif
                                </p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                {{ $booking->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('staff_performance_panel', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">Team &amp; Specialist Performance</h3>
                <a href="{{ route('business.staff.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">Manage Staff &rarr;</a>
            </div>
            @if ($staffPerformance->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300">Add staff members to track completed services and assigned sales.</div>
            @else
                <div class="space-y-2.5">
                    @foreach ($staffPerformance as $sp)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $sp['name'] }}</h4>
                                <span class="text-[11px] text-slate-300">{{ $sp['role'] }} · {{ $sp['completed'] }}/{{ $sp['bookings'] }} bookings completed</span>
                            </div>
                            <span class="text-xs font-black text-emerald-400 font-mono">${{ number_format($sp['revenue_usd'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('expiring_soon', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">Expiry &amp; Batch Monitor</h3>
                <a href="{{ route('business.products.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">Review Catalog &rarr;</a>
            </div>
            @if ($expiringSoonProducts->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300">
                    No products expiring within the next 30 days. Add expiry dates &amp; batch numbers on products for automatic alerts.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach ($expiringSoonProducts as $expItem)
                        @php $daysLeft = (int) now()->startOfDay()->diffInDays($expItem->expiry_date, false); @endphp
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $expItem->name_en }}</h4>
                                <p class="text-[11px] text-slate-300">
                                    Batch: {{ $expItem->batch_number ?: 'N/A' }} · Expires {{ $expItem->expiry_date?->format('Y-m-d') }}
                                </p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $daysLeft <= 7 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                {{ $daysLeft < 0 ? 'Expired' : ($daysLeft . 'd left') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('fashion_variant_breakdown', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">Size &amp; Color Variant Stock</h3>
                <a href="{{ route('business.products.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">All Styles &rarr;</a>
            </div>
            @if ($fashionVariantBreakdown->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300">
                    No size/color variants recorded yet. Add variants (S, M, L, XL / colors) inside any fashion product.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach ($fashionVariantBreakdown as $row)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $row['label'] }}</h4>
                                <span class="text-[11px] text-slate-300">{{ $row['variants_count'] }} SKU variants</span>
                            </div>
                            <span class="text-xs font-black text-sky-400 font-mono">{{ $row['stock_total'] }} pcs</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('peak_hours_compact', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <h3 class="text-base font-bold text-white mb-4">Peak Order Hours (7 Days)</h3>
            @if ($peakHoursCompact->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300">Peak hour patterns will appear once orders are completed.</div>
            @else
                <div class="space-y-2.5">
                    @foreach ($peakHoursCompact as $hour)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                            <span class="text-xs font-bold text-white font-mono">{{ $hour['label'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-orange-500/20 text-orange-300 text-xs font-bold">{{ $hour['orders'] }} orders</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('top_toppings', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <h3 class="text-base font-bold text-white mb-4">Most Popular Toppings &amp; Add-ons</h3>
            @if ($topToppings->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300">Add beverage toppings &amp; modifiers to track customer favorites.</div>
            @else
                <div class="space-y-2.5">
                    @foreach ($topToppings as $topping)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60">
                            <span class="text-xs font-bold text-white">{{ $topping['name'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold">{{ $topping['count'] }}x added</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('best_sellers', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <h3 class="text-base font-bold text-white mb-4">Top Selling {{ $terminology['catalog'] ?? 'Items' }}</h3>
            @if ($bestSellers->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300 font-medium">
                    No sales recorded yet. As orders are placed via POS or customer storefront, top performers will appear here.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($bestSellers as $item)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60 hover:border-gray-600 transition">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $item->product_name }}</h4>
                                <span class="text-[11px] text-slate-300 font-medium">{{ (float) $item->total_sold }} units sold</span>
                            </div>
                            <span class="text-xs font-black text-emerald-400 font-mono">${{ number_format((float) $item->total_earned, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('low_stock_products', $activePanels, true) || in_array('low_stock_ingredients', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">
                    {{ in_array('low_stock_ingredients', $activePanels, true) ? 'Low Stock Ingredients & Supplies' : 'Low Stock Product Alerts' }}
                </h3>
                @if(app(\App\Services\Business\CategoryExperienceService::class)->isModuleEnabled($business, 'inventory'))
                    <a href="{{ route('business.inventory.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">Restock &rarr;</a>
                @endif
            </div>
            @if ($lowStockProducts->isEmpty() && $lowStockIngredients->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300 font-medium">
                    All tracked stock levels are currently healthy.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach ($lowStockIngredients as $ing)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-rose-900/40">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $ing->name_en }}</h4>
                                <span class="text-[11px] text-slate-300">Alert at &le; {{ (float) $ing->low_stock_threshold }} {{ $ing->unit }}</span>
                            </div>
                            <span class="text-xs font-black text-rose-400 font-mono">{{ (float) $ing->current_stock }} {{ $ing->unit }}</span>
                        </div>
                    @endforeach
                    @foreach ($lowStockProducts as $lp)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-amber-900/40">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $lp->name_en }}</h4>
                                <span class="text-[11px] text-slate-300">Threshold: {{ (float) $lp->low_stock_threshold }}</span>
                            </div>
                            <span class="text-xs font-black text-amber-400 font-mono">{{ (float) $lp->stock_quantity }} left</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if(in_array('recent_products', $activePanels, true))
        <div class="p-6 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white">Recent {{ $terminology['catalog'] ?? 'Catalog Items' }}</h3>
                <a href="{{ route('business.products.index') }}" class="text-xs font-bold text-orange-400 hover:text-orange-300">View All</a>
            </div>
            @if ($recentProducts->isEmpty())
                <div class="py-8 text-center text-xs text-slate-300 font-medium">
                    No items added yet. Click <a href="{{ route('business.products.create') }}" class="text-orange-400 font-bold hover:underline">+ Add First {{ $terminology['item_singular'] ?? 'Item' }}</a> to start.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($recentProducts as $p)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-900/80 border border-gray-700/60 hover:border-gray-600 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gray-700 border border-gray-600 overflow-hidden shrink-0">
                                    @if ($p->primary_image_url)
                                        <img src="{{ $p->primary_image_url }}" alt="{{ $p->name_en }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-xs text-slate-300 font-bold">Item</div>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-white">{{ $p->name_en }}</h4>
                                    <span class="text-[10px] text-slate-300 font-medium">{{ $p->category?->name_en ?? 'General' }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-white font-mono">${{ number_format((float) $p->base_price, 2) }}</span>
                                <div class="text-[10px] font-bold {{ $p->is_available ? 'text-emerald-400' : 'text-slate-400' }}">
                                    {{ $p->is_available ? 'Active' : 'Hidden' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
