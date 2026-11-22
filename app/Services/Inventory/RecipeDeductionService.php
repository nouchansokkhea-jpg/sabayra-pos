<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Recipe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecipeDeductionService
{
    /**
     * Deduct raw ingredients for all items in an order that have associated recipes.
     */
    public function deductOrderIngredients(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $orderItem) {
                // Find matching recipe (variant-specific or base product)
                $recipe = null;

                if ($orderItem->variant_id) {
                    $recipe = Recipe::where('product_id', $orderItem->product_id)
                        ->where('variant_id', $orderItem->variant_id)
                        ->with('items.inventoryItem')
                        ->first();
                }

                if (!$recipe) {
                    $recipe = Recipe::where('product_id', $orderItem->product_id)
                        ->whereNull('variant_id')
                        ->with('items.inventoryItem')
                        ->first();
                }

                if (!$recipe || $recipe->items->isEmpty()) {
                    continue;
                }

                $multiplier = (float) $orderItem->quantity;

                foreach ($recipe->items as $recipeItem) {
                    $inventoryItem = $recipeItem->inventoryItem;
                    if (!$inventoryItem) {
                        continue;
                    }

                    $deductQuantity = (float) $recipeItem->quantity * $multiplier;

                    // Decrement stock
                    $inventoryItem->decrement('current_stock', $deductQuantity);
                    $balanceAfter = (float) $inventoryItem->fresh()->current_stock;

                    // Record movement
                    InventoryMovement::create([
                        'business_id' => $order->business_id,
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => Auth::id(),
                        'type' => 'recipe_deduction',
                        'quantity' => -$deductQuantity,
                        'balance_after' => $balanceAfter,
                        'cost_per_unit' => $inventoryItem->unit_cost,
                        'reference_id' => $order->order_number,
                        'reason' => "Recipe consumption: {$orderItem->product_name} x {$orderItem->quantity}",
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }
}
