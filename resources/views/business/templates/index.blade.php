<x-business-layout title="Storefront Templates" heading="Storefront Templates & Themes">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Active Template Design</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Select an industry-tailored storefront theme for your public digital menu (/{ $business->slug }).</p>
    </div>

    <!-- Template Gallery -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($templates as $key => $tmpl)
            <div class="p-5 bg-white dark:bg-gray-800 border {{ $activeTemplate === $key ? 'border-orange-500 ring-2 ring-orange-500/30' : 'border-gray-200 dark:border-gray-700' }} rounded-3xl shadow-xs flex flex-col justify-between transition hover:border-orange-400">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300">
                            {{ $tmpl['category'] }}
                        </span>
                        @if ($activeTemplate === $key)
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                Active
                            </span>
                        @endif
                    </div>

                    <h4 class="text-base font-black text-gray-900 dark:text-white">{{ $tmpl['name'] }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">{{ $tmpl['description'] }}</p>

                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-[10px] font-bold text-slate-400">Palette:</span>
                        <div class="w-4 h-4 rounded-full border border-white/20 shadow-xs" style="background-color: {{ $tmpl['primary_color'] }}"></div>
                        <div class="w-4 h-4 rounded-full border border-white/20 shadow-xs" style="background-color: {{ $tmpl['secondary_color'] }}"></div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                    <form method="POST" action="{{ route('business.storefront.templates.update') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="template" value="{{ $key }}" />
                        <input type="hidden" name="primary_color" value="{{ $tmpl['primary_color'] }}" />
                        <input type="hidden" name="secondary_color" value="{{ $tmpl['secondary_color'] }}" />

                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl text-xs font-bold transition {{ $activeTemplate === $key ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-default' : 'bg-orange-600 hover:bg-orange-500 text-white shadow-xs' }}">
                            {{ $activeTemplate === $key ? 'Currently Active' : 'Apply This Template' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

</div>
</x-business-layout>
