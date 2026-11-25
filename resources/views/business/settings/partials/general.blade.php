<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs">
    <div class="pb-4 mb-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <span>General Settings &amp; Storefront Theme</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Configure menu publishing status, public URL slug, QR mobile domain, and storefront theme customization.
            </p>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-gray-900 text-slate-700 dark:text-slate-300 font-mono">
            /{{ $business->slug }}
        </span>
    </div>

    <form method="POST" action="{{ route('business.settings.general.update') }}" class="space-y-7"
          x-data="{
              primaryColor: '{{ old('primary_color', ($business->theme_settings['primary_color'] ?? '#ea580c')) }}',
              setPreset(color, presetName) {
                  this.primaryColor = color;
                  document.getElementById('general_primary_color_input').value = color;
                  document.getElementById('general_theme_preset_input').value = presetName;
              }
          }">
        @csrf
        @method('PUT')

        <!-- Visibility & Routing -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    Publish Menu Status
                </label>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                    <option value="active" {{ old('status', $business->status) === 'active' ? 'selected' : '' }}>Published (Active &amp; Visible)</option>
                    <option value="inactive" {{ old('status', $business->status) === 'inactive' ? 'selected' : '' }}>Draft / Maintenance (Hidden)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    Public Menu Slug (URL) *
                </label>
                <div class="flex rounded-xl shadow-xs">
                    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-600 text-xs font-semibold dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        {{ rtrim(request()->schemeAndHttpHost(), '/') }}/
                    </span>
                    <input type="text" name="slug" value="{{ old('slug', $business->slug) }}" required
                           class="w-full text-xs rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-mono font-bold">
                </div>
                @error('slug') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    Custom Public Domain / Mobile Scan URL
                </label>
                <input type="text" name="public_url" value="{{ old('public_url', $business->theme_settings['public_url'] ?? '') }}"
                       placeholder="e.g. https://menu.yourshop.com"
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-mono">
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                    When configured, all Storefront &amp; Table QR standees encode this base URL for mobile scanning.
                </p>
            </div>
        </div>

        <!-- Operational Toggles -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700 space-y-3">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Storefront Behavior</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                    <input type="checkbox" name="show_khr_prices" value="1"
                           {{ old('show_khr_prices', $storedSettings['general.show_khr_prices'] ?? true) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Display KHR Price Equivalent</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Show dual USD &amp; Khmer Riel prices on customer menu.</span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                    <input type="checkbox" name="auto_accept_orders" value="1"
                           {{ old('auto_accept_orders', $storedSettings['general.auto_accept_orders'] ?? false) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Auto-Acknowledge Online Orders</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Highlight incoming QR orders directly in Kitchen Display.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Theme Presets & Styling -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-3">
                Menu Theme &amp; Customization
            </h4>

            <input type="hidden" name="theme_preset" id="general_theme_preset_input" value="{{ old('theme_preset', ($business->theme_settings['theme_preset'] ?? 'orange')) }}">

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
                @foreach ([
                    ['color' => '#ea580c', 'preset' => 'orange', 'name' => 'Warm Sunset', 'sub' => 'Khmer Orange', 'bg' => 'bg-orange-600'],
                    ['color' => '#d97706', 'preset' => 'coffee', 'name' => 'Coffee Amber', 'sub' => 'Cafe & Bakery', 'bg' => 'bg-amber-600'],
                    ['color' => '#059669', 'preset' => 'emerald', 'name' => 'Emerald Fresh', 'sub' => 'Organic & Healthy', 'bg' => 'bg-emerald-600'],
                    ['color' => '#7c3aed', 'preset' => 'luxury', 'name' => 'Royal Luxury', 'sub' => 'Fine Dining', 'bg' => 'bg-purple-600'],
                    ['color' => '#0f172a', 'preset' => 'minimal', 'name' => 'Modern Slate', 'sub' => 'Minimalist', 'bg' => 'bg-slate-900'],
                ] as $presetOpt)
                    <button type="button" @click="setPreset('{{ $presetOpt['color'] }}', '{{ $presetOpt['preset'] }}')"
                            class="p-3 rounded-xl border text-center transition hover:scale-[1.02]"
                            :class="primaryColor === '{{ $presetOpt['color'] }}' ? 'border-orange-500 ring-2 ring-orange-500/20 bg-orange-50/50 dark:bg-orange-950/30' : 'border-gray-200 dark:border-gray-700'">
                        <span class="w-6 h-6 rounded-full {{ $presetOpt['bg'] }} mx-auto block mb-1.5 shadow-sm"></span>
                        <span class="text-xs font-bold text-slate-800 dark:text-white block">{{ $presetOpt['name'] }}</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ $presetOpt['sub'] }}</span>
                    </button>
                @endforeach
            </div>

            @php $theme = $business->theme_settings ?? []; @endphp
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Primary Accent</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" name="primary_color" id="general_primary_color_input"
                               x-model="primaryColor"
                               class="w-10 h-10 rounded-lg border-0 cursor-pointer">
                        <span class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200" x-text="primaryColor"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Menu Layout</label>
                    <select name="layout" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="modern" {{ ($theme['layout'] ?? '') === 'modern' ? 'selected' : '' }}>Modern Clean Cards</option>
                        <option value="minimal" {{ ($theme['layout'] ?? '') === 'minimal' ? 'selected' : '' }}>Minimal Compact List</option>
                        <option value="grid" {{ ($theme['layout'] ?? '') === 'grid' ? 'selected' : '' }}>Visual Grid Showcase</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Typography Font</label>
                    <select name="font" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="Inter, 'Kantumruy Pro', sans-serif" {{ ($theme['font'] ?? '') === "Inter, 'Kantumruy Pro', sans-serif" ? 'selected' : '' }}>Inter + Kantumruy Pro</option>
                        <option value="Plus Jakarta Sans, Battambang, sans-serif" {{ ($theme['font'] ?? '') === "Plus Jakarta Sans, Battambang, sans-serif" ? 'selected' : '' }}>Plus Jakarta + Battambang</option>
                        <option value="Roboto, Siemreap, sans-serif" {{ ($theme['font'] ?? '') === "Roboto, Siemreap, sans-serif" ? 'selected' : '' }}>Roboto + Siemreap</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end pt-5 border-t border-gray-100 dark:border-gray-700">
            <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                Save General Settings
            </button>
        </div>
    </form>
</div>
