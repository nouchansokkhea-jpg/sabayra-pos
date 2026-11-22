<x-business-layout title="Business Settings" heading="Settings & Customization">
<div class="max-w-4xl space-y-6">

    <!-- Top Status & Preview Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-5 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div class="flex items-center space-x-3">
            <span class="w-3 h-3 rounded-full {{ $business->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                    Menu Status: <span class="{{ $business->status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ ucfirst($business->status) }}</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    {{ $business->status === 'active' ? 'Public digital menu is live and visible to customers.' : 'Menu is in draft/maintenance mode.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="/{{ $business->slug }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-xl transition shadow-xs">
                <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Preview Menu
            </a>
        </div>
    </div>

    <!-- Main Settings Form -->
    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <form method="POST" action="{{ route('business.settings.update') }}" enctype="multipart/form-data" class="space-y-8"
              x-data="{
                  primaryColor: '{{ old('primary_color', ($business->theme_settings['primary_color'] ?? '#ea580c')) }}',
                  setPreset(color, presetName) {
                      this.primaryColor = color;
                      document.getElementById('primary_color_input').value = color;
                      document.getElementById('theme_preset_input').value = presetName;
                  }
              }">
            @csrf
            @method('PUT')

            <!-- Section 1: Menu Publishing Status -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    1. Menu Visibility &amp; Publishing
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Publish Menu Status</label>
                        <select name="status" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <option value="active" {{ old('status', $business->status) === 'active' ? 'selected' : '' }}>🟢 Published (Active &amp; Accessible)</option>
                            <option value="inactive" {{ old('status', $business->status) === 'inactive' ? 'selected' : '' }}>🟡 Draft / Suspended (Hidden from Public)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Public Menu Slug (URL) *</label>
                        <div class="flex rounded-xl shadow-xs">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-700 text-xs font-semibold dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">menuhub.site/</span>
                            <input type="text" name="slug" value="{{ old('slug', $business->slug) }}" required
                                   class="w-full text-xs rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-mono font-bold">
                        </div>
                        @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Business Profile -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    2. Business Profile &amp; Location
                </h3>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Business Name *</label>
                        <input type="text" name="name" value="{{ old('name', $business->name) }}" required
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Business Category Type *</label>
                        <select name="business_type" required class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                            @foreach ($businessTypes as $key => $type)
                                <option value="{{ $key }}" {{ old('business_type', $business->business_type) === $key ? 'selected' : '' }}>
                                    {{ $type['name_en'] }} ({{ $type['name_km'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Tagline / Short Slogan</label>
                        <input type="text" name="tagline" value="{{ old('tagline', $business->tagline) }}" placeholder="e.g. Authentic Khmer Flavors &amp; Specialty Brews"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">About / Business Description</label>
                        <textarea name="description" rows="3" placeholder="Tell customers about your kitchen, specialties, and dining vibe..."
                                  class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">{{ old('description', $business->description) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $business->phone) }}" placeholder="+855 12 345 678"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">City / Province</label>
                        <input type="text" name="city" value="{{ old('city', $business->city ?? 'Phnom Penh') }}" placeholder="Phnom Penh, Siem Reap..."
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Full Address</label>
                        <input type="text" name="address" value="{{ old('address', $business->address) }}" placeholder="Street #, Sangkat, Khan..."
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
            </div>

            <!-- Section 3: Branding Images (Logo & Cover) -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    3. Logo &amp; Cover Banner
                </h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Business Logo -->
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40 space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-14 h-14 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 shrink-0 overflow-hidden shadow-xs flex items-center justify-center">
                                @if($business->logo_url)
                                    <img src="{{ $business->logo_url }}" alt="Logo" class="w-full h-full object-cover rounded-lg">
                                @else
                                    <span class="text-lg font-bold text-gray-400">LOGO</span>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-800 dark:text-gray-200">Business Logo</label>
                                <p class="text-[11px] text-slate-500">Square ratio (1:1), PNG/JPG up to 5MB</p>
                            </div>
                        </div>
                        <input type="file" name="logo_file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        <input type="url" name="logo_url" value="{{ old('logo_url', $business->logo_url) }}" placeholder="Or paste image URL (https://...)"
                               class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                    </div>

                    <!-- Cover Banner -->
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40 space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-20 h-14 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 shrink-0 overflow-hidden shadow-xs flex items-center justify-center">
                                @if($business->cover_url)
                                    <img src="{{ $business->cover_url }}" alt="Cover" class="w-full h-full object-cover rounded-lg">
                                @else
                                    <span class="text-[10px] font-bold text-gray-400">COVER</span>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-800 dark:text-gray-200">Cover Banner</label>
                                <p class="text-[11px] text-slate-500">Wide header banner (16:9), up to 10MB</p>
                            </div>
                        </div>
                        <input type="file" name="cover_file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        <input type="url" name="cover_url" value="{{ old('cover_url', $business->cover_url) }}" placeholder="Or paste cover URL (https://...)"
                               class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                    </div>
                </div>
            </div>

            <!-- Section 4: Opening Hours -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    4. Opening Hours (ម៉ោងបើកដំណើរការ)
                </h3>
                @php
                    $hours = $business->opening_hours ?? [];
                    $days = [
                        'monday' => 'Monday (ច័ន្ទ)',
                        'tuesday' => 'Tuesday (អង្គារ)',
                        'wednesday' => 'Wednesday (ពុធ)',
                        'thursday' => 'Thursday (ព្រហស្បតិ៍)',
                        'friday' => 'Friday (សុក្រ)',
                        'saturday' => 'Saturday (សៅរ៍)',
                        'sunday' => 'Sunday (អាទិត្យ)',
                    ];
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($days as $dayKey => $dayLabel)
                        @php
                            $dayData = $hours[$dayKey] ?? ['open' => '07:00', 'close' => '22:00', 'closed' => false];
                        @endphp
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 w-28">{{ $dayLabel }}</span>
                            <div class="flex items-center gap-1.5">
                                <input type="time" name="opening_hours[{{ $dayKey }}][open]" value="{{ $dayData['open'] ?? '07:00' }}"
                                       class="text-xs py-1 px-2 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <span class="text-slate-400">-</span>
                                <input type="time" name="opening_hours[{{ $dayKey }}][close]" value="{{ $dayData['close'] ?? '22:00' }}"
                                       class="text-xs py-1 px-2 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 5: Social Media Links -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    5. Social Media &amp; Direct Messaging Links
                </h3>
                @php $social = ($business->theme_settings['social_links'] ?? []); @endphp
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Telegram Handle / Link</label>
                        <div class="flex rounded-xl shadow-xs">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-xs dark:bg-gray-700 dark:border-gray-600">t.me/</span>
                            <input type="text" name="social_links[telegram]" value="{{ old('social_links.telegram', $social['telegram'] ?? $business->telegram_handle) }}" placeholder="channel_or_username"
                                   class="w-full text-xs rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Facebook Page URL</label>
                        <input type="url" name="social_links[facebook]" value="{{ old('social_links.facebook', $social['facebook'] ?? '') }}" placeholder="https://facebook.com/yourpage"
                               class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Instagram Profile</label>
                        <input type="text" name="social_links[instagram]" value="{{ old('social_links.instagram', $social['instagram'] ?? '') }}" placeholder="@instagram_account"
                               class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">TikTok Profile</label>
                        <input type="text" name="social_links[tiktok]" value="{{ old('social_links.tiktok', $social['tiktok'] ?? '') }}" placeholder="@tiktok_handle"
                               class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
            </div>

            <!-- Section 6: Theme Customizer -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    6. Menu Customization &amp; Themes (រចនាបទ Menu)
                </h3>

                <input type="hidden" name="theme_preset" id="theme_preset_input" value="{{ old('theme_preset', ($business->theme_settings['theme_preset'] ?? 'orange')) }}">

                <!-- Theme Presets Selection -->
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2.5">Choose Theme Preset</label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <button type="button" @click="setPreset('#ea580c', 'orange')"
                                class="p-3 rounded-xl border text-center transition hover:scale-105"
                                :class="primaryColor === '#ea580c' ? 'border-orange-500 ring-2 ring-orange-500/20 bg-orange-50/50 dark:bg-orange-950/30' : 'border-gray-200 dark:border-gray-700'">
                            <span class="w-6 h-6 rounded-full bg-orange-600 mx-auto block mb-1.5 shadow-sm"></span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white block">Warm Sunset</span>
                            <span class="text-[10px] text-slate-500">Khmer Orange</span>
                        </button>

                        <button type="button" @click="setPreset('#d97706', 'coffee')"
                                class="p-3 rounded-xl border text-center transition hover:scale-105"
                                :class="primaryColor === '#d97706' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/50 dark:bg-amber-950/30' : 'border-gray-200 dark:border-gray-700'">
                            <span class="w-6 h-6 rounded-full bg-amber-600 mx-auto block mb-1.5 shadow-sm"></span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white block">Coffee Amber</span>
                            <span class="text-[10px] text-slate-500">Cafe &amp; Bakery</span>
                        </button>

                        <button type="button" @click="setPreset('#059669', 'emerald')"
                                class="p-3 rounded-xl border text-center transition hover:scale-105"
                                :class="primaryColor === '#059669' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-950/30' : 'border-gray-200 dark:border-gray-700'">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 mx-auto block mb-1.5 shadow-sm"></span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white block">Emerald Fresh</span>
                            <span class="text-[10px] text-slate-500">Organic &amp; Healthy</span>
                        </button>

                        <button type="button" @click="setPreset('#7c3aed', 'luxury')"
                                class="p-3 rounded-xl border text-center transition hover:scale-105"
                                :class="primaryColor === '#7c3aed' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/50 dark:bg-purple-950/30' : 'border-gray-200 dark:border-gray-700'">
                            <span class="w-6 h-6 rounded-full bg-purple-600 mx-auto block mb-1.5 shadow-sm"></span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white block">Royal Luxury</span>
                            <span class="text-[10px] text-slate-500">Bar &amp; Fine Dining</span>
                        </button>

                        <button type="button" @click="setPreset('#0f172a', 'minimal')"
                                class="p-3 rounded-xl border text-center transition hover:scale-105"
                                :class="primaryColor === '#0f172a' ? 'border-slate-800 ring-2 ring-slate-800/20 bg-slate-100 dark:bg-slate-900' : 'border-gray-200 dark:border-gray-700'">
                            <span class="w-6 h-6 rounded-full bg-slate-900 mx-auto block mb-1.5 shadow-sm"></span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white block">Modern Slate</span>
                            <span class="text-[10px] text-slate-500">Minimalist</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @php $theme = $business->theme_settings ?? []; @endphp
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Change Primary Color</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="primary_color" id="primary_color_input"
                                   x-model="primaryColor"
                                   class="w-10 h-10 rounded-lg border-0 cursor-pointer">
                            <span class="text-xs font-mono font-bold" x-text="primaryColor"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Change Layout</label>
                        <select name="layout" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                            <option value="modern" {{ ($theme['layout'] ?? '') === 'modern' ? 'selected' : '' }}>Modern Clean Cards (Recommended)</option>
                            <option value="minimal" {{ ($theme['layout'] ?? '') === 'minimal' ? 'selected' : '' }}>Minimal Compact List</option>
                            <option value="grid" {{ ($theme['layout'] ?? '') === 'grid' ? 'selected' : '' }}>Visual Grid Showcase</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Change Font</label>
                        <select name="font" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                            <option value="Inter, 'Kantumruy Pro', sans-serif" {{ ($theme['font'] ?? '') === "Inter, 'Kantumruy Pro', sans-serif" ? 'selected' : '' }}>Inter + Kantumruy Pro (Crisp &amp; Modern)</option>
                            <option value="Plus Jakarta Sans, Battambang, sans-serif" {{ ($theme['font'] ?? '') === "Plus Jakarta Sans, Battambang, sans-serif" ? 'selected' : '' }}>Plus Jakarta Sans + Battambang</option>
                            <option value="Roboto, Siemreap, sans-serif" {{ ($theme['font'] ?? '') === "Roboto, Siemreap, sans-serif" ? 'selected' : '' }}>Roboto + Siemreap</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 7: Currency & Tax -->
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    7. Currency &amp; Pricing Standards
                </h3>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Primary Currency *</label>
                        <select name="default_currency" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <option value="USD" {{ old('default_currency', $business->default_currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="KHR" {{ old('default_currency', $business->default_currency) === 'KHR' ? 'selected' : '' }}>KHR (៛)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Exchange Rate (1 USD = KHR) *</label>
                        <input type="number" step="1" name="exchange_rate_khr" value="{{ old('exchange_rate_khr', (int)$business->exchange_rate_khr) }}" required
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Tax Rate (VAT / %)</label>
                        <input type="number" step="0.01" name="tax_percent" value="{{ old('tax_percent', $business->tax_percent) }}"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
            </div>

            <!-- Submit Button & Live Preview Shortcut -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-100 dark:border-gray-700">
                <a href="/{{ $business->slug }}" target="_blank" class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400 inline-flex items-center gap-1.5">
                    <span>Preview Menu in New Tab &rarr;</span>
                </a>
                <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                    Save Profile &amp; Customization
                </button>
            </div>
        </form>
    </div>
</div>
</x-business-layout>
