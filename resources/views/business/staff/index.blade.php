<x-business-layout title="Staff & Roles" heading="Staff & Role Management">
<div class="space-y-6" x-data="{
    transferModalOpen: false,
    editModalOpen: false,
    editingMember: { id: '', name: '', email: '', role: 'cashier', branch_id: '', is_active: true }
}">
    <!-- Top Action Bar & Rule Notice -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-xs text-slate-400">Manage business ownership, team members, cashiers, waitstaff, and kitchen operators</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v4a1 1 0 102 0V7z" clip-rule="evenodd" />
                </svg>
                Strict 1 Business = 1 Owner Enforced
            </span>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-2xl text-xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-400 rounded-2xl text-xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-red-400 hover:text-white">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-400 rounded-2xl text-xs space-y-1">
            <div class="font-bold">Please correct the following errors:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (Owner Card + Staff List) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- SECTION 1: BUSINESS OWNER (Single Authoritative Owner) -->
            <div class="bg-gradient-to-br from-amber-500/10 via-slate-900/90 to-slate-900 border border-amber-500/30 rounded-2xl p-6 shadow-xl relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">
                            👑
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-white uppercase tracking-wider">Designated Business Owner</h2>
                            <p class="text-[11px] text-slate-400">Sole owner account with full authority, billing access, and settings control</p>
                        </div>
                    </div>

                    @if ($isOwner)
                        <button type="button" @click="transferModalOpen = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Transfer Ownership
                        </button>
                    @endif
                </div>

                <div class="bg-slate-950/60 border border-amber-500/20 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-amber-600 to-amber-400 flex items-center justify-center font-black text-xl text-slate-950 shadow-md">
                                {{ strtoupper(substr($owner->name ?? 'O', 0, 1)) }}
                            </div>
                            <span class="absolute -bottom-1 -right-1 bg-slate-900 text-amber-400 rounded-full p-0.5 border border-amber-500/40 text-xs" title="Authoritative Owner">👑</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-base text-white">{{ $owner->name ?? 'Unknown Owner' }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                    Primary Owner
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    Active
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 flex items-center gap-1.5 flex-wrap">
                                <span>✉️ {{ $owner->email ?? 'No email' }}</span>
                                @if ($owner->phone)
                                    <span class="text-slate-500">&bull;</span>
                                    <span>📞 {{ $owner->phone }}</span>
                                @endif
                            </p>
                            <p class="text-[11px] text-slate-400">
                                Scope: <strong class="text-slate-200">All Branches & Unlimited Privileges</strong>
                                &bull; Owner Since: {{ $ownerMember?->joined_at?->format('M d, Y') ?? $business->created_at->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: OPERATIONAL STAFF MEMBERS (Excludes Owner) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <span>👥 Staff Members</span>
                            <span class="px-2 py-0.5 rounded-full text-xs bg-slate-800 text-slate-300 border border-slate-700 font-bold">
                                {{ $staffMembers->count() }}
                            </span>
                        </h2>
                        <p class="text-[11px] text-slate-400">Assigned business team members with role-based operational permissions</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse ($staffMembers as $member)
                        <div class="p-4 bg-slate-900 border border-slate-800 hover:border-slate-700/80 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-sm">
                                    {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-bold text-white text-sm">
                                            {{ $member->user->name ?? 'Unknown Staff' }}
                                        </p>

                                        <!-- Role Badges (Color-coded) -->
                                        @if ($member->role === 'manager')
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-purple-500/20 text-purple-300 border border-purple-500/30">Manager</span>
                                        @elseif ($member->role === 'cashier')
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-blue-500/20 text-blue-300 border border-blue-500/30">Cashier</span>
                                        @elseif ($member->role === 'waiter')
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Waiter</span>
                                        @elseif ($member->role === 'kitchen')
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">Kitchen Staff</span>
                                        @elseif ($member->role === 'inventory')
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">Inventory</span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-slate-700 text-slate-300 capitalize">{{ $member->role }}</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-400">
                                        {{ $member->user->email ?? '' }}
                                        @if ($member->user->phone) &bull; {{ $member->user->phone }} @endif
                                        &bull; Branch: <span class="text-slate-300 font-semibold">{{ $member->branch->name ?? 'All Branches' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <!-- Status Badge -->
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $member->is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                                    {{ $member->is_active ? 'Active' : 'Disabled' }}
                                </span>

                                @if ($canManageStaff)
                                    <!-- Edit Button -->
                                    <button type="button"
                                            @click="editingMember = {
                                                id: '{{ $member->id }}',
                                                name: '{{ addslashes($member->user->name ?? '') }}',
                                                email: '{{ addslashes($member->user->email ?? '') }}',
                                                role: '{{ $member->role }}',
                                                branch_id: '{{ $member->branch_id ?? '' }}',
                                                is_active: {{ $member->is_active ? 'true' : 'false' }}
                                            }; editModalOpen = true"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition">
                                        Edit
                                    </button>

                                    <!-- Quick Toggle Active Status -->
                                    <form method="POST" action="{{ route('business.staff.update', $member) }}" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="role" value="{{ $member->role }}">
                                        <input type="hidden" name="branch_id" value="{{ $member->branch_id }}">
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg {{ $member->is_active ? 'bg-slate-800 hover:bg-slate-700 text-slate-400' : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300' }} transition">
                                            {{ $member->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                @endif

                                @if ($isOwner)
                                    <!-- Remove Staff Member -->
                                    <form method="POST" action="{{ route('business.staff.destroy', $member) }}"
                                          onsubmit="return confirm('Are you sure you want to remove {{ addslashes($member->user->name ?? 'this staff member') }} from {{ addslashes($business->name) }}?');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 transition" title="Remove staff member">
                                            Remove
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-slate-900 border border-slate-800 rounded-2xl">
                            <p class="text-3xl mb-2">👥</p>
                            <h4 class="text-sm font-bold text-white mb-1">No Staff Members Added Yet</h4>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                You currently operate as the single owner. Use the form on the right to invite managers, cashiers, waitstaff, or kitchen operators.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Sidebar: Add Staff Form -->
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-xl h-fit sticky top-20">
            <div class="mb-4">
                <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                    <span>➕ Add Team Member</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Invite a staff member with role-specific access</p>
            </div>

            <form method="POST" action="{{ route('business.staff.store') }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Staff member name" value="{{ old('name') }}"
                           class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Email Address *</label>
                    <input type="email" name="email" required placeholder="staff@example.com" value="{{ old('email') }}"
                           class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Phone Number</label>
                    <input type="text" name="phone" placeholder="+855 ..." value="{{ old('phone') }}"
                           class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">
                        Role & Permissions *
                        <span class="text-[10px] font-normal text-amber-400 ml-1">(Owner role excluded)</span>
                    </label>
                    <select name="role" required class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                        <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager (Menu, Orders & Reports)</option>
                        <option value="cashier" {{ old('role') === 'cashier' || !old('role') ? 'selected' : '' }}>Cashier (POS & Fast Checkout)</option>
                        <option value="waiter" {{ old('role') === 'waiter' ? 'selected' : '' }}>Waiter (Table Ordering & Serving)</option>
                        <option value="kitchen" {{ old('role') === 'kitchen' ? 'selected' : '' }}>Kitchen Staff (Kitchen Display System)</option>
                        <option value="inventory" {{ old('role') === 'inventory' ? 'selected' : '' }}>Inventory Manager (Stock & Recipes)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Assigned Branch</label>
                    <select name="branch_id" class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                        <option value="">All Branches (Full Access)</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') === $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Login Password (optional)</label>
                    <input type="password" name="password" minlength="8" placeholder="Auto-generated if left empty"
                           class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                </div>

                <button type="submit" class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center gap-1.5">
                    <span>Add Staff Member</span>
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL 1: OWNERSHIP TRANSFER MODAL -->
    <div x-show="transferModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs transition-opacity" @click="transferModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-slate-900 border border-amber-500/40 rounded-2xl p-6 shadow-2xl space-y-4" @click.stop>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">👑</span>
                        <div>
                            <h3 class="text-base font-black text-white">Transfer Business Ownership</h3>
                            <p class="text-xs text-slate-400">{{ $business->name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="transferModalOpen = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <div class="p-3.5 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-300 space-y-1">
                    <p class="font-bold">⚠️ Warning: Single Owner Policy</p>
                    <p class="text-slate-300 leading-relaxed">
                        SABAYRA strictly enforces <strong>ONE BUSINESS = ONE OWNER</strong>. Transferring ownership will irrevocably transfer all administrative, fiscal, and platform authority to the new owner. You will step down as Owner and be reassigned to the selected staff role.
                    </p>
                </div>

                <form method="POST" action="{{ route('business.staff.transfer-ownership') }}" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Select New Business Owner *</label>
                        <select name="new_owner_id" required class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-amber-500 focus:outline-none">
                            <option value="">-- Choose verified team member --</option>
                            @foreach ($staffMembers as $member)
                                <option value="{{ $member->user->id }}">{{ $member->user->name }} ({{ $member->user->email }}) - Current: {{ ucfirst($member->role) }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">If the person is not in this list, add them as a staff member first.</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Your New Role in this Business *</label>
                        <select name="previous_owner_role" required class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-amber-500 focus:outline-none">
                            <option value="manager" selected>Manager (Recommended)</option>
                            <option value="cashier">Cashier</option>
                            <option value="waiter">Waiter</option>
                            <option value="kitchen">Kitchen Staff</option>
                            <option value="inventory">Inventory Manager</option>
                        </select>
                    </div>

                    @if (!Auth::user()?->isSuperAdmin())
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Confirm Your Current Password *</label>
                            <input type="password" name="password" required placeholder="Enter your current password"
                                   class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-amber-500 focus:outline-none">
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="transferModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                onclick="return confirm('Are you completely sure you want to permanently transfer ownership of {{ addslashes($business->name) }}?');"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-md transition">
                            Confirm Ownership Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: EDIT STAFF MEMBER MODAL -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs transition-opacity" @click="editModalOpen = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4" @click.stop>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-white">Edit Staff Member</h3>
                        <p class="text-xs text-slate-400" x-text="editingMember.name + ' (' + editingMember.email + ')'"></p>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <form method="POST" :action="'/app/staff/' + editingMember.id" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Role & Permissions *</label>
                        <select name="role" x-model="editingMember.role" required class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                            <option value="manager">Manager (Menu, Orders & Reports)</option>
                            <option value="cashier">Cashier (POS & Fast Checkout)</option>
                            <option value="waiter">Waiter (Table Ordering & Serving)</option>
                            <option value="kitchen">Kitchen Staff (Kitchen Display System)</option>
                            <option value="inventory">Inventory Manager (Stock & Recipes)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Assigned Branch</label>
                        <select name="branch_id" x-model="editingMember.branch_id" class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Account Status</label>
                        <select name="is_active" :value="editingMember.is_active ? 1 : 0" class="w-full text-xs rounded-xl border border-slate-700 bg-slate-950 text-white px-3 py-2.5 focus:border-orange-500 focus:outline-none">
                            <option value="1">Active</option>
                            <option value="0">Disabled / Suspended</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-orange-600 hover:bg-orange-700 text-white shadow-md transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-business-layout>
