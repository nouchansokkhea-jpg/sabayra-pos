<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BusinessWorkspaceController extends Controller
{
    /**
     * Show form to create/register a new business.
     */
    public function create(): View
    {
        $businessTypes = config('menuhub.business_types');
        $currencies = config('menuhub.currencies');

        return view('business.onboarding.create', compact('businessTypes', 'currencies'));
    }

    /**
     * Store a newly created business and establish ownership.
     */
    public function store(Request $request): RedirectResponse
    {
        // Auto-slugify input if provided (e.g. "Bar-Premium" -> "bar-premium")
        if ($request->filled('slug')) {
            $cleanedSlug = Str::slug($request->input('slug'));
            $request->merge([
                'slug' => !empty($cleanedSlug) ? $cleanedSlug : null,
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:businesses,slug', 'regex:/^[a-z0-9-]+$/'],
            'business_type' => ['required', 'string', 'in:' . implode(',', array_keys(config('menuhub.business_types')))],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram_handle' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'default_currency' => ['required', 'string', 'in:USD,KHR'],
            'exchange_rate_khr' => ['required', 'numeric', 'min:1000'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'slug.regex' => 'The public menu slug may only contain lowercase letters, numbers, and dashes (e.g. bar-premium).',
            'slug.unique' => 'This public menu URL slug is already taken. Please pick another one.',
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            if (empty($baseSlug)) {
                // For Khmer or non-Latin business names, generate clean unique URL slug
                $baseSlug = 'shop-' . Str::lower(Str::random(6));
            }
            $slug = $baseSlug;
            $counter = 1;
            while (Business::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $validated['owner_id'] = $request->user()->id;
        $validated['status'] = 'active';
        $validated['tax_percent'] = $validated['tax_percent'] ?? 0.00;

        // Default theme
        $validated['theme_settings'] = [
            'primary_color' => '#f97316',
            'secondary_color' => '#1f2937',
            'font' => 'Inter, Siemreap, sans-serif',
            'layout' => 'modern',
        ];

        // 1. Create Business
        $business = Business::create($validated);

        // 2. Create Default Main Branch
        $mainBranch = BusinessBranch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'phone' => $business->phone,
            'address' => $business->address,
            'is_main' => true,
            'is_active' => true,
        ]);

        // 3. Create Business Member Link with Owner Role
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $request->user()->id,
            'role' => 'owner',
            'branch_id' => $mainBranch->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // 4. Attach Free Plan by default
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $freePlan->id,
                'billing_cycle' => 'monthly',
                'price' => 0.00,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYears(10), // Free tier
                'payment_method' => 'free',
            ]);
        }

        // 5. Set as active session workspace
        session(['active_business_id' => $business->id]);
        TenantContext::set($business);

        return redirect()->route('business.dashboard')
            ->with('success', "Welcome to {$business->name}! Your business workspace is ready.");
    }

    /**
     * Switch active business workspace context.
     */
    public function switch(Request $request, Business $business): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user() ?? Auth::user();

        if (!$user || !$user->belongsToBusiness($business)) {
            abort(403, 'Unauthorized access to this business.');
        }

        session(['active_business_id' => $business->id]);
        TenantContext::set($business);

        return redirect()->route('business.dashboard')
            ->with('success', "Switched workspace to {$business->name}.");
    }
}
