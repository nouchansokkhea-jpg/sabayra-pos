<div id="online-booking" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 sm:p-6 shadow-sm space-y-5"
     x-data="{
        serviceId: '{{ $bookingServices->first()?->id ?? '' }}',
        staffId: '',
        bookingDate: '{{ now()->toDateString() }}',
        selectedTime: '',
        slots: [],
        loadingSlots: false,
        customerName: localStorage.getItem('customer_name') || '',
        customerPhone: localStorage.getItem('customer_phone') || '',
        notes: '',
        submittingBooking: false,
        bookingError: '',
        async loadSlots() {
            if (!this.bookingDate) return;
            this.loadingSlots = true;
            this.bookingError = '';
            try {
                const params = new URLSearchParams({
                    date: this.bookingDate,
                    service_id: this.serviceId || '',
                    staff_id: this.staffId || '',
                });
                const res = await fetch('{{ route('public.booking-slots', ['business_slug' => $business->slug]) }}?' + params.toString());
                const data = await res.json();
                this.slots = data.slots || [];
                const firstAvail = this.slots.find(s => s.available);
                this.selectedTime = firstAvail ? firstAvail.time : '';
            } catch (e) {
                this.bookingError = 'Unable to load available time slots.';
            } finally {
                this.loadingSlots = false;
            }
        },
        async submitBooking() {
            this.bookingError = '';
            if (!this.serviceId || !this.bookingDate || !this.selectedTime || !this.customerName.trim() || !this.customerPhone.trim()) {
                this.bookingError = 'Please select a service, available time slot, and enter your name and phone number.';
                return;
            }
            this.submittingBooking = true;
            localStorage.setItem('customer_name', this.customerName.trim());
            localStorage.setItem('customer_phone', this.customerPhone.trim());
            try {
                const res = await fetch('{{ route('public.book', ['business_slug' => $business->slug]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        service_product_id: this.serviceId,
                        staff_member_id: this.staffId || null,
                        reservation_date: this.bookingDate,
                        reservation_time: this.selectedTime,
                        customer_name: this.customerName.trim(),
                        customer_phone: this.customerPhone.trim(),
                        special_requests: this.notes || null,
                    }),
                });
                const data = await res.json();
                if (res.ok && data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    this.bookingError = data.error || 'Could not confirm appointment.';
                    await this.loadSlots();
                }
            } catch (e) {
                this.bookingError = 'Connection error. Please try again.';
            } finally {
                this.submittingBooking = false;
            }
        }
     }"
     x-init="loadSlots()"
     @select-booking-service.window="serviceId = $event.detail; loadSlots(); $el.scrollIntoView({ behavior: 'smooth', block: 'center' });">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-gray-100 dark:border-gray-800">
        <div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
                Online Appointment Scheduler
            </span>
            <h2 class="text-lg font-black text-gray-900 dark:text-white mt-1">Book Your Visit / កក់ណាត់ជួប</h2>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Real-time staff &amp; slot availability</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
        <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">1. Service *</label>
            <select x-model="serviceId" @change="loadSlots()"
                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 text-xs font-semibold">
                @foreach($bookingServices as $svc)
                    <option value="{{ $svc->id }}">{{ $svc->localized_name }} · ${{ number_format((float) $svc->effective_price, 2) }} ({{ $svc->duration_minutes ?? 60 }}m)</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">2. Specialist</label>
            <select x-model="staffId" @change="loadSlots()"
                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 text-xs font-semibold">
                <option value="">Any Available Staff</option>
                @foreach($staffMembers as $staff)
                    <option value="{{ $staff->id }}">{{ $staff->user?->name ?? 'Specialist' }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">3. Date *</label>
            <input type="date" x-model="bookingDate" min="{{ now()->toDateString() }}" @change="loadSlots()"
                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-xs font-semibold">
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">4. Available Time Slots</label>
        <div x-show="loadingSlots" class="text-xs text-gray-500">Checking live availability...</div>
        <div x-show="!loadingSlots" class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-xs">
            <template x-for="slot in slots" :key="slot.time">
                <button type="button"
                        :disabled="!slot.available"
                        @click="selectedTime = slot.time"
                        :style="selectedTime === slot.time ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                        :class="!slot.available
                            ? 'bg-gray-100 dark:bg-gray-800/40 text-gray-400 line-through cursor-not-allowed border-gray-200 dark:border-gray-800'
                            : (selectedTime === slot.time ? 'font-bold border-transparent shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-orange-400')"
                        class="py-2 rounded-xl border font-mono text-center transition"
                        x-text="slot.time">
                </button>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
        <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Your Name *</label>
            <input type="text" x-model="customerName" placeholder="Full Name"
                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-xs">
        </div>
        <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Phone Number *</label>
            <input type="text" x-model="customerPhone" placeholder="012 345 678"
                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-xs">
        </div>
        <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Notes (Optional)</label>
            <input type="text" x-model="notes" placeholder="Preferences or requests..."
                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-xs">
        </div>
    </div>

    <div x-show="bookingError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-bold text-rose-700 dark:text-rose-300" x-text="bookingError"></div>

    <button type="button" @click="submitBooking()" :disabled="submittingBooking || !selectedTime"
            style="background-color: var(--brand-primary);"
            class="w-full py-3 rounded-xl text-white font-black text-sm shadow-md hover:opacity-95 disabled:opacity-50 transition">
        <span x-show="!submittingBooking">Confirm Appointment Booking</span>
        <span x-show="submittingBooking">Booking...</span>
    </button>
</div>
