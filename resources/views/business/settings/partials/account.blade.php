<div class="space-y-6">
    <!-- Personal Profile & Language Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs">
        <div class="pb-4 mb-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <span>Account Settings</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Update your personal sign-in profile, phone number, and workspace language preference.
            </p>
        </div>

        <form method="POST" action="{{ route('business.settings.account.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Personal Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+855 12 345 678"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Workspace Language *</label>
                    <select name="preferred_locale" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                        <option value="en" {{ old('preferred_locale', $user->preferred_locale ?? app()->getLocale()) === 'en' ? 'selected' : '' }}>English (EN)</option>
                        <option value="km" {{ old('preferred_locale', $user->preferred_locale ?? app()->getLocale()) === 'km' ? 'selected' : '' }}>ភាសាខ្មែរ (Khmer)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                    Save Account Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Password Update Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs">
        <div class="pb-4 mb-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-base font-black text-slate-900 dark:text-white">Update Account Password</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Confirm your current password to set a strong new password for your SABAYRA account.
            </p>
        </div>

        <form method="POST" action="{{ route('business.settings.account.password') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Current Password *</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">New Password *</label>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('profile.edit') }}" class="text-xs font-semibold text-slate-500 hover:text-rose-500 transition">
                    Manage Account Deletion &rarr;
                </a>
                <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-orange-600 dark:hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
