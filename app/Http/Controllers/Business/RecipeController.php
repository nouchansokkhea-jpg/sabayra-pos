<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    /**
     * Display recipe list and ingredient linkings.
     */
    public function index(): View
    {
        $business = TenantContext::get();

        $recipes = Recipe::with(['product', 'items.inventoryItem'])
            ->whereHas('product', fn($q) => $q->where('business_id', $business->id))
            ->get();

        $products = Product::where('business_id', $business->id)->orderBy('name_en')->get();
        $inventoryItems = InventoryItem::where('business_id', $business->id)->where('is_active', true)->get();

        return view('business.recipes.index', compact('business', 'recipes', 'products', 'inventoryItems'));
    }

    /**
     * Store or update a recipe formula for a product.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'yield_quantity' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|uuid|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string|max:30',
        ]);

        DB::transaction(function () use ($validated) {
            $recipe = Recipe::updateOrCreate(
                ['product_id' => $validated['product_id']],
                [
                    'yield_quantity' => $validated['yield_quantity'],
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            // Re-sync recipe items
            RecipeItem::where('recipe_id', $recipe->id)->delete();

            foreach ($validated['items'] as $item) {
                RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                ]);
            }
        });

        return back()->with('status', 'Recipe formula saved successfully!');
    }

    /**
     * Delete a recipe formula.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($recipe->product->business_id !== $business->id, 403);

        $recipe->delete();

        return back()->with('status', 'Recipe deleted.');
    }
}
