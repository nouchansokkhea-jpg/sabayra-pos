<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Business\CategoryExperienceService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BusinessWorkspaceController extends Controller
{
    public function __construct(
        private readonly CategoryExperienceService $categoryExperience
    ) {
    }

    /**
     * Show form to create/register a new business.
     */
    public function create(): View
    {
        $businessTypes = config('menuhub.business_types');
        $currencies = config('menuhub.currencies');
        $categoryCatalog = collect($this->categoryExperience->categories())->map(function (array $cfg, string $key): array {
            $theme = $this->categoryExperience->defaultThemeForCategory($key);
            $features = (array) ($cfg['features'] ?? []);

            return [
                'key' => $key,
                'name_en' => (string) ($cfg['name_en'] ?? $key),
                'name_km' => (string) ($cfg['name_km'] ?? $key),
                'description_en' => (string) ($cfg['description_en'] ?? ''),
                'description_km' => (string) ($cfg['description_km'] ?? ''),
                'icon' => (string) ($cfg['icon'] ?? 'building'),
                'pos_mode' => (string) ($cfg['pos']['mode'] ?? 'standard'),
                'storefront_mode' => (string) ($cfg['storefront']['mode'] ?? 'catalog'),
                'headline_en' => (string) ($cfg['dashboard']['headline_en'] ?? ''),
                'headline_km' => (string) ($cfg['dashboard']['headline_km'] ?? ''),
                'kpis' => array_values((array) ($cfg['dashboard']['kpis'] ?? [])),
                'active_modules' => array_keys(array_filter($features, static fn ($enabled): bool => (bool) $enabled)),
                'primary_color' => $theme['primary_color'],
                'layout' => $theme['layout'],
            ];
        })->all();
        $configurableModules = CategoryExperienceService::CONFIGURABLE_MODULES;

        return view('business.onboarding.create', compact(
            'businessTypes',
            'currencies',
            'categoryCatalog',
            'configurableModules'
        ));
    }

    /**
     * Store a newly created business and establish ownership.
     */
    public function store(Request $request): RedirectResponse
    {
        // Auto-slugify input if provided (supporting English, Khmer, and Unicode characters)
        if ($request->filled('slug')) {
            $raw = trim($request->input('slug'));
            $cleanedSlug = preg_replace('/[\s_]+/u', '-', $raw);
            $cleanedSlug = preg_replace('/[^\p{L}\p{M}\p{N}\-]+/u', '', $cleanedSlug);
            $cleanedSlug = trim($cleanedSlug, '-');
            $request->merge([
                'slug' => !empty($cleanedSlug) ? mb_strtolower($cleanedSlug, 'UTF-8') : null,
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:businesses,slug', 'regex:/^[\p{L}\p{M}\p{N}\-]+$/u'],
            'business_type' => ['required', 'string', 'in:' . implode(',', array_keys(config('menuhub.business_types')))],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram_handle' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'default_currency' => ['required', 'string', 'in:USD,KHR'],
            'exchange_rate_khr' => ['required', 'numeric', 'min:1000'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'custom_modules' => ['nullable', 'array'],
        ], [
            'slug.regex' => 'The public menu slug may only contain letters (English/Khmer), numbers, and dashes.',
            'slug.unique' => 'This public menu URL slug is already taken. Please pick another one.',
        ]);

        $customModulesInput = (array) $request->input('custom_modules', []);
        unset($validated['custom_modules']);

        if (empty($validated['slug'])) {
            $raw = trim($validated['name']);
            $baseSlug = preg_replace('/[\s_]+/u', '-', $raw);
            $baseSlug = preg_replace('/[^\p{L}\p{M}\p{N}\-]+/u', '', $baseSlug);
            $baseSlug = trim($baseSlug, '-');
            $baseSlug = mb_strtolower($baseSlug, 'UTF-8');

            if (empty($baseSlug)) {
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

        // Category-tailored default theme
        $validated['theme_settings'] = $this->categoryExperience->defaultThemeForCategory($validated['business_type']);

        // 1. Create Business
        $business = Business::create($validated);

        // 1b. Initialize smart category experience defaults & optional custom modules
        $this->categoryExperience->initializeDefaultsForBusiness($business, preserveCustomModules: false);

        if ($validated['business_type'] === 'other' && $customModulesInput !== []) {
            $sanitizedModules = [];
            foreach (CategoryExperienceService::CONFIGURABLE_MODULES as $moduleKey) {
                $sanitizedModules[$moduleKey] = filter_var(
                    $customModulesInput[$moduleKey] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
            }
            \App\Models\BusinessSetting::setValue($business->id, 'category.custom_modules', $sanitizedModules);
        }

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
