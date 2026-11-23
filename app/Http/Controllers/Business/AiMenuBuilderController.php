<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiMenuBuilderController extends Controller
{
    /**
     * Display AI Menu Builder guided setup wizard.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $existingCategoriesCount = Category::where('business_id', $business->id)->count();
        $existingProductsCount = Product::where('business_id', $business->id)->count();

        return view('business.ai.menu-builder', compact('business', 'existingCategoriesCount', 'existingProductsCount'));
    }

    /**
     * Commit user-approved AI generated menu categories and products.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $items = $request->input('items', []);
        if (empty($items)) {
            return back()->withErrors(['error' => 'No items were selected for generation.']);
        }

        $created = 0;
        foreach ($items as $item) {
            if (empty($item['name_en'])) {
                continue;
            }

            $catName = !empty($item['category']) ? trim($item['category']) : 'Specialties';
            $category = Category::firstOrCreate(
                ['business_id' => $business->id, 'name_en' => $catName],
                ['slug' => Str::slug($catName) ?: 'cat-' . rand(100, 999)]
            );

            $baseSlug = Str::slug($item['name_en']) ?: 'dish-' . rand(100, 999);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('business_id', $business->id)->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            Product::create([
                'business_id' => $business->id,
                'category_id' => $category->id,
                'name_en' => trim($item['name_en']),
                'name_km' => !empty($item['name_km']) ? trim($item['name_km']) : null,
                'slug' => $slug,
                'base_price' => !empty($item['price']) ? (float) $item['price'] : 3.50,
                'description_en' => !empty($item['description_en']) ? trim($item['description_en']) : null,
                'description_km' => !empty($item['description_km']) ? trim($item['description_km']) : null,
                'is_available' => true,
            ]);

            $created++;
        }

        return redirect()->route('business.products.index')
            ->with('status', "AI Menu Builder successfully generated {$created} menu items for your business!");
    }
}
