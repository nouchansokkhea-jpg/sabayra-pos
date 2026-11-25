<x-guest-layout>
    <div class="bg-white rounded-[28px] shadow-2xl shadow-indigo-950/60 p-8 sm:p-10 border border-slate-100"
         x-data="{ useRecovery: {{ old('recovery_code') ? 'true' : 'false' }} }">
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="inline-flex flex-col items-center group">
                <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-14 h-14 rounded-2xl object-contain mb-2 shadow-md shadow-orange-500/20" />
                <span class="text-2xl font-black tracking-tight text-slate-900">SABAYRA</span>
            </a>
        </div>

        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 border border-orange-200 text-[11px] font-bold text-orange-700 mb-3">
                Two-Factor Verification
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Verify Your Identity</h1>
            <p class="text-xs text-slate-500 mt-1" x-show="!useRecovery">
                Enter the 6-digit code from your authenticator app for <span class="font-semibold text-slate-700">{{ $userEmailMasked }}</span>.
            </p>
            <p class="text-xs text-slate-500 mt-1" x-show="useRecovery" x-cloak>
                Enter one of your single-use backup recovery codes to sign in.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-xl">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
            @csrf

            <div x-show="!useRecovery">
                <label for="code" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Authentication Code
                </label>
                <input id="code"
                       type="text"
                       name="code"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="6"
                       placeholder="000000"
                       x-bind:disabled="useRecovery"
                       autofocus
                       class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-center font-mono text-lg tracking-[0.35em] placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
            </div>

            <div x-show="useRecovery" x-cloak>
                <label for="recovery_code" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Recovery Code
                </label>
                <input id="recovery_code"
                       type="text"
                       name="recovery_code"
                       value="{{ old('recovery_code') }}"
                       autocomplete="off"
                       placeholder="XXXXX-XXXXX"
                       x-bind:disabled="!useRecovery"
                       class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-center font-mono text-sm uppercase tracking-widest placeholder-slate-400 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" />
            </div>

            <button type="submit"
                    class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white font-bold text-sm shadow-lg shadow-orange-500/25 transition active:scale-[0.99]">
                Verify &amp; Sign In
            </button>
        </form>

        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
            <button type="button"
                    @click="useRecovery = !useRecovery"
                    class="font-bold text-orange-600 hover:text-orange-700 transition">
                <span x-show="!useRecovery">Use a recovery code instead</span>
                <span x-show="useRecovery" x-cloak>Use authenticator app code</span>
            </button>

            <a href="{{ route('login') }}" class="font-semibold text-slate-500 hover:text-slate-700 transition">
                Back to Sign In
            </a>
        </div>
    </div>
</x-guest-layout>
