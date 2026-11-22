<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = BusinessBranch::latest()->get();
        return view('business.branches.index', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_main' => ['boolean'],
        ]);

        $validated['is_main'] = $request->boolean('is_main');
        $validated['is_active'] = true;

        if ($validated['is_main']) {
            BusinessBranch::query()->update(['is_main' => false]);
        }

        BusinessBranch::create($validated);

        return back()->with('success', "Branch '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, BusinessBranch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_main' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_main'] = $request->boolean('is_main');
        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_main']) {
            BusinessBranch::where('id', '!=', $branch->id)->update(['is_main' => false]);
        }

        $branch->update($validated);

        return back()->with('success', "Branch '{$branch->name}' updated.");
    }

    public function destroy(BusinessBranch $branch): RedirectResponse
    {
        if ($branch->is_main) {
            return back()->with('error', 'Cannot delete the main business branch.');
        }

        $name = $branch->name;
        $branch->delete();

        return back()->with('success', "Branch '{$name}' removed.");
    }
}
