<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Services\Business\CategoryExperienceService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        private readonly CategoryExperienceService $categoryExperience
    ) {
    }

    /**
     * Display a listing of products or services tailored to the active business category.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        $context = $this->catalogContext($business);
        $query = Product::with(['category', 'variants', 'addons']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_km', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($catId = $request->input('category_id')) {
            $query->where('category_id', $catId);
        }

        if ($request->input('filter') === 'low_stock') {
            $query->where('track_inventory', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        } elseif ($request->input('filter') === 'expiring_soon') {
            $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(30));
        }

        if ($request->has('available')) {
            $query->where('is_available', $request->boolean('available'));
        }

        $products = $query->orderBy('sort_order')->latest()->paginate(15)->withQueryString();
        $categories = Category::orderBy('name_en')->get();

        return view('business.products.index', array_merge(compact('products', 'categories'), $context));
    }

    /**
     * Show form for creating a new product or service.
     */
    public function create(): View
    {
        $business = TenantContext::get();
        $context = $this->catalogContext($business);
        $categories = Category::orderBy('sort_order')->get();
        $addons = ProductAddon::where('is_available', true)->get();

        return view('business.products.create', array_merge(compact('categories', 'addons'), $context));
    }

    /**
     * Store newly created product or service with category-specific fields and variant matrix.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'expiry_date' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
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
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.shade' => ['nullable', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'variants.*.price_adjustment' => ['required_with:variants', 'numeric'],
        ]);

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name_en']) ?: ('item-' . Str::lower(Str::random(6)));
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

        DB::transaction(function () use ($validated): void {
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
                            'size' => $variantData['size'] ?? null,
                            'color' => $variantData['color'] ?? null,
                            'shade' => $variantData['shade'] ?? null,
                            'sku' => $variantData['sku'] ?? null,
                            'barcode' => $variantData['barcode'] ?? null,
                            'stock_quantity' => (float) ($variantData['stock_quantity'] ?? 0.00),
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
     * Show form for editing product or service.
     */
    public function edit(Product $product): View
    {
        $business = TenantContext::get();
        $context = $this->catalogContext($business);
        $product->load(['variants', 'addons']);
        $categories = Category::orderBy('sort_order')->get();
        $addons = ProductAddon::where('is_available', true)->get();

        return view('business.products.edit', array_merge(compact('product', 'categories', 'addons'), $context));
    }

    /**
     * Update product, category fields, and optional variant matrix.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_km' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'batch_number' => ['nullable', 'string', 'max:120'],
            'expiry_date' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
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
            'variants.*.id' => ['nullable', 'string'],
            'variants.*.name_en' => ['required_with:variants', 'string', 'max:100'],
            'variants.*.name_km' => ['nullable', 'string', 'max:100'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.shade' => ['nullable', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'variants.*.price_adjustment' => ['required_with:variants', 'numeric'],
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

        DB::transaction(function () use ($product, $validated, $request): void {
            $product->update($validated);

            if (isset($validated['addons'])) {
                $product->addons()->sync($validated['addons']);
            } else {
                $product->addons()->detach();
            }

            if ($request->boolean('variants_submitted')) {
                $keptIds = [];
                foreach ((array) ($validated['variants'] ?? []) as $variantData) {
                    if (empty($variantData['name_en'])) {
                        continue;
                    }
                    $attributes = [
                        'name_en' => $variantData['name_en'],
                        'name_km' => $variantData['name_km'] ?? null,
                        'size' => $variantData['size'] ?? null,
                        'color' => $variantData['color'] ?? null,
                        'shade' => $variantData['shade'] ?? null,
                        'sku' => $variantData['sku'] ?? null,
                        'barcode' => $variantData['barcode'] ?? null,
                        'stock_quantity' => (float) ($variantData['stock_quantity'] ?? 0.00),
                        'price_adjustment' => (float) ($variantData['price_adjustment'] ?? 0.00),
                    ];
                    if (!empty($variantData['id'])) {
                        $existing = ProductVariant::where('product_id', $product->id)->find($variantData['id']);
                        if ($existing) {
                            $existing->update($attributes);
                            $keptIds[] = $existing->id;
                            continue;
                        }
                    }
                    $created = ProductVariant::create(array_merge($attributes, ['product_id' => $product->id]));
                    $keptIds[] = $created->id;
                }
                ProductVariant::where('product_id', $product->id)->whereNotIn('id', $keptIds)->delete();
            }
        });

        return redirect()->route('business.products.index')
            ->with('success', "Product '{$product->name_en}' updated successfully.");
    }

    /**
     * Resolve category catalog context flags and terminology.
     *
     * @return array<string, mixed>
     */
    private function catalogContext(?\App\Models\Business $business): array
    {
        $categoryConfig = $this->categoryExperience->configFor($business);
        $categoryMeta = $this->categoryExperience->meta($business);
        $terminology = $this->categoryExperience->terminology($business);
        $features = (array) ($categoryConfig['features'] ?? []);
        $posConfig = $this->categoryExperience->posConfig($business);

        return [
            'categoryConfig' => $categoryConfig,
            'categoryMeta' => $categoryMeta,
            'terminology' => $terminology,
            'catalogFeatures' => $features,
            'defaultProductType' => (string) ($posConfig['default_product_type'] ?? 'standard'),
        ];
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
