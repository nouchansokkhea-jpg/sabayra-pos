<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs">
    <div class="pb-4 mb-6 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            <span>Business Information &amp; Branding</span>
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Update your business identity, location, logo/cover banners, opening hours, and storefront social links.
        </p>
    </div>

    <form method="POST" action="{{ route('business.settings.business.update') }}" enctype="multipart/form-data" class="space-y-7">
        @csrf
        @method('PUT')

        <!-- Profile Fields -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Business Name *</label>
                <input type="text" name="name" value="{{ old('name', $business->name) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Business Category *</label>
                <select name="business_type" required class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    @foreach ($businessTypes as $key => $type)
                        <option value="{{ $key }}" {{ old('business_type', $business->business_type) === $key ? 'selected' : '' }}>
                            {{ $type['name_en'] }} ({{ $type['name_km'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Tagline / Slogan</label>
                <input type="text" name="tagline" value="{{ old('tagline', $business->tagline) }}" placeholder="e.g. Authentic Khmer Flavors &amp; Specialty Brews"
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Business Description</label>
                <textarea name="description" rows="3" placeholder="Describe your kitchen, menu specialties, and dining experience..."
                          class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">{{ old('description', $business->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Business Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $business->phone) }}" placeholder="+855 12 345 678"
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Business Contact Email</label>
                <input type="email" name="email" value="{{ old('email', $business->email) }}" placeholder="contact@yourbusiness.com"
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">City / Province</label>
                <input type="text" name="city" value="{{ old('city', $business->city ?? 'Phnom Penh') }}" placeholder="Phnom Penh, Siem Reap..."
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Full Street Address</label>
                <input type="text" name="address" value="{{ old('address', $business->address) }}" placeholder="Street #, Sangkat, Khan..."
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>

        <!-- Branding Media -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-4">Logo &amp; Cover Banner</h4>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40 space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-14 h-14 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 shrink-0 overflow-hidden shadow-xs flex items-center justify-center">
                            @if($business->logo_url)
                                <img src="{{ $business->logo_url }}" alt="Logo" class="w-full h-full object-cover rounded-lg">
                            @else
                                <span class="text-xs font-bold text-gray-400">LOGO</span>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">Business Logo</label>
                            <p class="text-[11px] text-slate-500">Square ratio (1:1), PNG/JPG up to 5MB</p>
                        </div>
                    </div>
                    <input type="file" name="logo_file" accept="image/*" class="w-full text-xs text-gray-600 dark:text-slate-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                    <input type="url" name="logo_url" value="{{ old('logo_url', $business->logo_url) }}" placeholder="Or paste image URL (https://...)"
                           class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                </div>

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
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">Cover Banner</label>
                            <p class="text-[11px] text-slate-500">Wide banner (16:9), up to 10MB</p>
                        </div>
                    </div>
                    <input type="file" name="cover_file" accept="image/*" class="w-full text-xs text-gray-600 dark:text-slate-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                    <input type="url" name="cover_url" value="{{ old('cover_url', $business->cover_url) }}" placeholder="Or paste cover URL (https://...)"
                           class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                </div>
            </div>
        </div>

        <!-- Opening Hours -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-4">Opening Hours (ម៉ោងបើកដំណើរការ)</h4>
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
                    @php $dayData = $hours[$dayKey] ?? ['open' => '07:00', 'close' => '22:00']; @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 text-xs">
                        <span class="font-bold text-slate-800 dark:text-slate-200 w-32">{{ $dayLabel }}</span>
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

        <!-- Storefront Social Links -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-4">Public Social Media Links</h4>
            @php $social = ($business->theme_settings['social_links'] ?? []); @endphp
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Storefront Telegram Link</label>
                    <div class="flex rounded-xl shadow-xs">
                        <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-600 text-xs font-semibold dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">t.me/</span>
                        <input type="text" name="social_links[telegram]" value="{{ old('social_links.telegram', $social['telegram'] ?? $business->telegram_handle) }}" placeholder="channel_or_username"
                               class="w-full text-xs rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Facebook Page URL</label>
                    <input type="text" name="social_links[facebook]" value="{{ old('social_links.facebook', $social['facebook'] ?? '') }}" placeholder="https://facebook.com/yourpage"
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Instagram Profile</label>
                    <input type="text" name="social_links[instagram]" value="{{ old('social_links.instagram', $social['instagram'] ?? '') }}" placeholder="@instagram_account"
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">TikTok Profile</label>
                    <input type="text" name="social_links[tiktok]" value="{{ old('social_links.tiktok', $social['tiktok'] ?? '') }}" placeholder="@tiktok_handle"
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end pt-5 border-t border-gray-100 dark:border-gray-700">
            <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                Save Business Information
            </button>
        </div>
    </form>
</div>
