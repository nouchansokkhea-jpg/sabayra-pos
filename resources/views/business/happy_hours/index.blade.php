<x-business-layout title="Happy Hours" heading="Happy Hours Promotions">
<div class="space-y-6" x-data="{ addHappy: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Scheduled Happy Hour Windows</h3>
            <p class="text-xs text-slate-400">Automatically activate discounts during quiet hours or evening drinks.</p>
        </div>
        <button @click="addHappy = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Add Happy Hour
        </button>
    </div>

    <!-- Happy Hours List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($happyHours as $hh)
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-black text-gray-900 dark:text-white">{{ $hh->name }}</h4>
                    <span class="text-xs font-black text-emerald-600 font-mono">{{ (float) $hh->discount_percent }}% OFF</span>
                </div>

                <div class="text-xs text-slate-500 space-y-1">
                    <p>Time: <strong class="text-gray-900 dark:text-white">{{ $hh->start_time }} &minus; {{ $hh->end_time }}</strong></p>
                    <p>Days: <span class="capitalize text-slate-700 dark:text-slate-300 font-bold">{{ implode(', ', $hh->days_of_week ?? []) }}</span></p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                    <form method="POST" action="{{ route('business.happy-hours.toggle', $hh) }}">
                        @csrf
                        <button type="submit" class="font-bold text-blue-500">
                            {{ $hh->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('business.happy-hours.destroy', $hh) }}" onsubmit="return confirm('Delete happy hour?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-bold text-red-500">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No happy hour promotions scheduled.
            </div>
        @endforelse
    </div>

    <!-- Add Modal -->
    <div x-show="addHappy" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addHappy = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">New Happy Hour Window</h3>

            <form method="POST" action="{{ route('business.happy-hours.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Promotion Name</label>
                    <input type="text" name="name" placeholder="e.g. Sunset Cocktails 20% Off" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Discount Percent (%)</label>
                    <input type="number" step="1" name="discount_percent" value="20" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Start Time</label>
                        <input type="time" name="start_time" value="16:00" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">End Time</label>
                        <input type="time" name="end_time" value="19:00" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Days of Week</label>
                    <div class="grid grid-cols-4 gap-1 text-[11px]">
                        @foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $key => $lbl)
                            <label class="flex items-center space-x-1">
                                <input type="checkbox" name="days_of_week[]" value="{{ $key }}" checked class="rounded text-orange-600" />
                                <span>{{ $lbl }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addHappy = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Save</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
