<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HappyHour;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HappyHourController extends Controller
{
    /**
     * Display automated Happy Hour scheduled discount windows.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $happyHours = HappyHour::where('business_id', $business->id)->get();
        $categories = Category::where('business_id', $business->id)->get();

        return view('business.happy_hours.index', compact('business', 'happyHours', 'categories'));
    }

    /**
     * Store a happy hour rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'discount_percent' => 'required|numeric|min:1|max:90',
            'start_time' => 'required',
            'end_time' => 'required',
            'days_of_week' => 'required|array|min:1',
            'applicable_categories' => 'nullable|array',
        ]);

        $validated['business_id'] = $business->id;
        $validated['is_active'] = true;

        HappyHour::create($validated);

        return back()->with('status', 'Happy Hour promotion saved!');
    }

    /**
     * Toggle happy hour active status.
     */
    public function toggle(HappyHour $happyHour): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($happyHour->business_id !== $business->id, 403);

        $happyHour->update(['is_active' => !$happyHour->is_active]);

        return back()->with('status', 'Happy Hour status toggled.');
    }

    /**
     * Delete a happy hour rule.
     */
    public function destroy(HappyHour $happyHour): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($happyHour->business_id !== $business->id, 403);

        $happyHour->delete();

        return back()->with('status', 'Happy hour deleted.');
    }
}
