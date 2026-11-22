<x-guest-layout>
    <div class="bg-white rounded-[28px] shadow-2xl shadow-indigo-950/60 p-8 sm:p-10 border border-slate-100">
        <!-- Brand Header (Matching readmenu.site/login) -->
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
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Welcome Back</h1>
            <p class="text-xs text-slate-500 mt-1">Sign in to manage your menu &amp; store</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-xl">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Email Address
                </label>
                <div class="relative">
                    <input id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autofocus
                           autocomplete="username"
                           placeholder="your@email.com"
                           class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
                </div>
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
                           autocomplete="current-password"
                           placeholder="Password"
                           class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
                    
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 transition"
                            aria-label="Toggle password visibility">
                        <!-- Eye icon -->
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <!-- Eye Slash icon -->
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label for="remember_me" class="inline-flex items-center text-slate-600 cursor-pointer">
                    <input id="remember_me"
                           type="checkbox"
                           name="remember"
                           class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500 transition cursor-pointer">
                    <span class="ms-2">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="font-bold text-orange-600 hover:text-orange-700 transition">
                        Forgot Password?
                    </a>
                @endif
            </div>

            <!-- Submit Button (Vibrant Orange Button with Icon) -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-orange-500 via-orange-600 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm tracking-wide shadow-lg shadow-orange-500/35 transition hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Sign In</span>
                </button>
            </div>
        </form>

        <!-- Don't have an account -->
        <div class="text-center mt-6 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-500">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-bold text-orange-600 hover:text-orange-700 ml-1 transition">
                    Create One Free
                </a>
            </p>
        </div>
    </div>
</x-guest-layout>
