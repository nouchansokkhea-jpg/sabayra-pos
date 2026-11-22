<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products for the active business.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'variants', 'addons']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_km', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($catId = $request->input('category_id')) {
            $query->where('category_id', $catId);
        }

        if ($request->has('available')) {
            $query->where('is_available', $request->boolean('available'));
        }

        $products = $query->orderBy('sort_order')->latest()->paginate(15)->withQueryString();
        $categories = Category::orderBy('name_en')->get();

        return view('business.products.index', compact('products', 'categories'));
    }

    /**
     * Show form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::orderBy('sort_order')->get();
        $addons = ProductAddon::where('is_available', true)->get();

        return view('business.products.create', compact('categories', 'addons'));
    }

    /**
     * Store newly created product.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
            'product_type' => ['required', 'string', 'in:standard,food,beverage,retail,service'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'primary_image_url' => ['nullable', 'url', 'max:1000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'unit' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['integer', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['exists:product_addons,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.name_en' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.name_km' => ['nullable', 'string', 'max:100'],
            'variants.*.price_adjustment' => ['required_with:variants', 'numeric'],
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name_en']);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Upload to Supabase Storage if file is provided
        if ($request->hasFile('image')) {
            $uploadedUrl = (new \App\Services\Storage\SupabaseStorageService())->upload($request->file('image'), 'products');
            if ($uploadedUrl) {
                $validated['primary_image_url'] = $uploadedUrl;
            }
        }
        unset($validated['image']);

        $validated['is_available'] = $request->boolean('is_available', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);
        $validated['track_inventory'] = $request->boolean('track_inventory', false);
        $validated['unit'] = $validated['unit'] ?? 'item';
        $validated['cost_price'] = $validated['cost_price'] ?? 0.00;

        DB::transaction(function () use ($validated, $request) {
            $product = Product::create($validated);

            // Sync Add-ons
            if (!empty($validated['addons'])) {
                $product->addons()->sync($validated['addons']);
            }

            // Create Variants
            if (!empty($validated['variants'])) {
                foreach ($validated['variants'] as $variantData) {
                    if (!empty($variantData['name_en'])) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'name_en' => $variantData['name_en'],
                            'name_km' => $variantData['name_km'] ?? null,
                            'price_adjustment' => (float) ($variantData['price_adjustment'] ?? 0.00),
                        ]);
                    }
                }
            }
        });

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$validated['name_en']}' created successfully.");
    }

    /**
     * Show form for editing product.
     */
    public function edit(Product $product): View
    {
        $product->load(['variants', 'addons']);
        $categories = Category::orderBy('sort_order')->get();
        $addons = ProductAddon::where('is_available', true)->get();

        return view('business.products.edit', compact('product', 'categories', 'addons'));
    }

    /**
     * Update product.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description_en' => ['nullable', 'string'],
            'description_km' => ['nullable', 'string'],
            'product_type' => ['required', 'string', 'in:standard,food,beverage,retail,service'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'primary_image_url' => ['nullable', 'url', 'max:1000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'unit' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['integer', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['exists:product_addons,id'],
        ]);

        // Upload to Supabase Storage if file is provided
        if ($request->hasFile('image')) {
            $uploadedUrl = (new \App\Services\Storage\SupabaseStorageService())->upload($request->file('image'), 'products');
            if ($uploadedUrl) {
                $validated['primary_image_url'] = $uploadedUrl;
            }
        }
        unset($validated['image']);

        $validated['is_available'] = $request->boolean('is_available');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['track_inventory'] = $request->boolean('track_inventory');

        DB::transaction(function () use ($product, $validated) {
            $product->update($validated);

            if (isset($validated['addons'])) {
                $product->addons()->sync($validated['addons']);
            } else {
                $product->addons()->detach();
            }
        });

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$product->name_en}' updated successfully.");
    }

    /**
     * Toggle availability.
     */
    public function toggleAvailability(Product $product): RedirectResponse
    {
        $product->update(['is_available' => !$product->is_available]);

        $statusStr = $product->is_available ? 'marked as available' : 'marked as unavailable';
        return back()->with('success', "Product '{$product->name_en}' is now {$statusStr}.");
    }

    /**
     * Delete product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name_en;
        $product->delete();

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$name}' deleted.");
    }
}
