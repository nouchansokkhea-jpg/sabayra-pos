<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories for the current business.
     */
    public function index(): View
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->get();
        return view('business.categories.index', compact('categories'));
    }

    /**
     * Show form for creating a new category.
     */
    public function create(): View
    {
        return view('business.categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name_en']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Category::create($validated);

        return redirect()->route('business.categories.index')
            ->with('success', "Category '{$validated['name_en']}' created successfully.");
    }

    /**
     * Show form for editing a category.
     */
    public function edit(Category $category): View
    {
        return view('business.categories.edit', compact('category'));
    }

    /**
     * Update category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('business.categories.index')
            ->with('success', "Category '{$category->name_en}' updated successfully.");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update(['is_active' => !$category->is_active]);

        $statusStr = $category->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Category '{$category->name_en}' has been {$statusStr}.");
    }

    /**
     * Delete category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $name = $category->name_en;
        $category->delete();

        return redirect()->route('business.categories.index')
            ->with('success', "Category '{$name}' deleted.");
    }
}
