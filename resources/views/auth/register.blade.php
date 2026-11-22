<x-guest-layout>
    <div class="bg-white rounded-[28px] shadow-2xl shadow-indigo-950/60 p-8 sm:p-10 border border-slate-100">
        <!-- Brand Header (Matching readmenu.site) -->
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="inline-flex flex-col items-center group">
                <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-14 h-14 rounded-2xl object-contain mb-2 shadow-md shadow-orange-500/20 group-hover:scale-105 transition duration-300" />
                <div class="flex items-center gap-1.5">
                    <span class="text-2xl font-black tracking-tight text-slate-900">SABAYRA</span>
                    <span class="text-[9px] font-bold text-orange-600 bg-orange-50 border border-orange-200 px-1.5 py-0.5 rounded">CAMBODIA</span>
                </div>
                <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase mt-0.5">Smarter Business, Simpler Every Day.</p>
            </a>
        </div>

        <!-- Heading -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Free Account</h1>
            <p class="text-xs text-slate-500 mt-1">Start your 14-day free trial • No credit card required</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-xl">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Full Name
                </label>
                <input id="name"
                       type="text"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       autofocus
                       autocomplete="name"
                       placeholder="e.g. Sokha Chan"
                       class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Email Address
                </label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autocomplete="username"
                       placeholder="your@email.com"
                       class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
            </div>

            <!-- Password with Eye Toggle -->
            <div x-data="{ showPassword: false }">
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Password
                </label>
                <div class="relative">
                    <input id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           required
                           autocomplete="new-password"
                           placeholder="At least 8 characters"
                           class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
                    
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 transition"
                            aria-label="Toggle password visibility">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div x-data="{ showConfirmPassword: false }">
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Confirm Password
                </label>
                <div class="relative">
                    <input id="password_confirmation"
                           :type="showConfirmPassword ? 'text' : 'password'"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           placeholder="Repeat password"
                           class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
                    
                    <button type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 transition"
                            aria-label="Toggle password confirmation visibility">
                        <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showConfirmPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button (Vibrant Orange Button with Rocket Icon) -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-orange-500 via-orange-600 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm tracking-wide shadow-lg shadow-orange-500/35 transition hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Create My Free Account</span>
                </button>
            </div>
        </form>

        <!-- Terms notice -->
        <p class="text-[11px] text-center text-slate-400 mt-4">
            By creating an account, you agree to SABAYRA's Terms of Service and Privacy Policy.
        </p>

        <!-- Already registered -->
        <div class="text-center mt-6 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-500">
                Already registered?
                <a href="{{ route('login') }}" class="font-bold text-orange-600 hover:text-orange-700 ml-1 transition">
                    Sign In
                </a>
            </p>
        </div>
    </div>
</x-guest-layout>
