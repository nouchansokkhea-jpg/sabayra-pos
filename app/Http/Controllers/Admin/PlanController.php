<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display listing of SaaS plans.
     */
    public function index(): View
    {
        $plans = Plan::withCount('subscriptions')->orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show form to create new plan.
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Store new plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'product_limit' => ['required', 'integer', 'min:1'],
            'branch_limit' => ['required', 'integer', 'min:1'],
            'staff_limit' => ['required', 'integer', 'min:1'],
            'has_pos' => ['boolean'],
            'has_inventory' => ['boolean'],
            'has_online_ordering' => ['boolean'],
            'has_analytics' => ['boolean'],
            'has_custom_branding' => ['boolean'],
            'has_ai_assistant' => ['boolean'],
            'has_ai_menu_creator' => ['boolean'],
            'has_ai_marketing' => ['boolean'],
            'has_ai_forecasting' => ['boolean'],
            'ai_monthly_credits' => ['required', 'integer', 'min:0', 'max:1000000'],
            'ai_max_tokens_per_request' => ['required', 'integer', 'min:256', 'max:16384'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['has_pos'] = (bool) ($request->has('has_pos'));
        $validated['has_inventory'] = (bool) ($request->has('has_inventory'));
        $validated['has_online_ordering'] = (bool) ($request->has('has_online_ordering'));
        $validated['has_analytics'] = (bool) ($request->has('has_analytics'));
        $validated['has_custom_branding'] = (bool) ($request->has('has_custom_branding'));
        $validated['has_ai_assistant'] = $request->boolean('has_ai_assistant');
        $validated['has_ai_menu_creator'] = $request->boolean('has_ai_menu_creator');
        $validated['has_ai_marketing'] = $request->boolean('has_ai_marketing');
        $validated['has_ai_forecasting'] = $request->boolean('has_ai_forecasting');
        $validated['is_active'] = (bool) ($request->has('is_active'));
        $validated['is_popular'] = (bool) ($request->has('is_popular'));

        Plan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan created successfully.');
    }

    /**
     * Show form to edit plan.
     */
    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update plan details.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('plans')->ignore($plan->id)],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'product_limit' => ['required', 'integer', 'min:1'],
            'branch_limit' => ['required', 'integer', 'min:1'],
            'staff_limit' => ['required', 'integer', 'min:1'],
            'ai_monthly_credits' => ['required', 'integer', 'min:0', 'max:1000000'],
            'ai_max_tokens_per_request' => ['required', 'integer', 'min:256', 'max:16384'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['has_pos'] = $request->boolean('has_pos');
        $validated['has_inventory'] = $request->boolean('has_inventory');
        $validated['has_online_ordering'] = $request->boolean('has_online_ordering');
        $validated['has_analytics'] = $request->boolean('has_analytics');
        $validated['has_custom_branding'] = $request->boolean('has_custom_branding');
        $validated['has_ai_assistant'] = $request->boolean('has_ai_assistant');
        $validated['has_ai_menu_creator'] = $request->boolean('has_ai_menu_creator');
        $validated['has_ai_marketing'] = $request->boolean('has_ai_marketing');
        $validated['has_ai_forecasting'] = $request->boolean('has_ai_forecasting');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popular'] = $request->boolean('is_popular');

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Delete plan if no active subscriptions exist.
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete plan because active subscriptions are currently attached.');
        }

        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Plan deleted successfully.');
    }
}
