<x-guest-layout>
    <div class="bg-white rounded-[28px] shadow-2xl shadow-indigo-950/60 p-8 sm:p-10 border border-slate-100">
        <!-- Brand Header -->
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
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Reset Password</h1>
            <p class="text-xs text-slate-500 mt-1">Enter your email and we'll send a recovery link</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                {{ session('status') }}
            </div>
        @endif

        @if (session('direct_reset_url'))
            <div class="mb-4 p-4 rounded-2xl bg-orange-50 border border-orange-200 text-xs text-orange-950 space-y-2">
                <p class="font-bold text-orange-900 flex items-center gap-1.5">
                    <span>⚡</span>
                    <span>Direct Password Reset Link Available:</span>
                </p>
                <p class="text-slate-600">You can click below to set your new password immediately:</p>
                <a href="{{ session('direct_reset_url') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition shadow-sm text-xs">
                    <span>Reset Password Now &rarr;</span>
                </a>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-xl">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

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
                       autofocus
                       placeholder="your@email.com"
                       class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-orange-500 via-orange-600 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm tracking-wide shadow-lg shadow-orange-500/35 transition hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Send Reset Link</span>
                </button>
            </div>
        </form>

        <div class="text-center mt-6 pt-4 border-t border-slate-100">
            <a href="{{ route('login') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 transition flex items-center justify-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Sign In</span>
            </a>
        </div>
    </div>
</x-guest-layout>
