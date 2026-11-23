<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    /**
     * Display Translation Manager for bilingual English & Khmer content.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        $tab = $request->query('tab', 'products');

        $products = Product::where('business_id', $business->id)->orderBy('name_en')->get();
        $categories = Category::where('business_id', $business->id)->orderBy('name_en')->get();

        $missingKmProductsCount = $products->filter(fn($p) => empty($p->name_km))->count();
        $missingKmCategoriesCount = $categories->filter(fn($c) => empty($c->name_km))->count();

        return view('business.translations.index', compact(
            'business',
            'products',
            'categories',
            'tab',
            'missingKmProductsCount',
            'missingKmCategoriesCount'
        ));
    }

    /**
     * Bulk save translations.
     */
    public function update(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        if ($request->has('products')) {
            foreach ($request->input('products', []) as $id => $data) {
                Product::where('business_id', $business->id)->where('id', $id)->update([
                    'name_km' => $data['name_km'] ?? null,
                    'description_km' => $data['description_km'] ?? null,
                ]);
            }
        }

        if ($request->has('categories')) {
            foreach ($request->input('categories', []) as $id => $data) {
                Category::where('business_id', $business->id)->where('id', $id)->update([
                    'name_km' => $data['name_km'] ?? null,
                ]);
            }
        }

        return back()->with('status', 'Translations saved successfully!');
    }
}
