<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display inventory raw materials, stock status, and recent movements.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();

        $query = InventoryItem::where('business_id', $business->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_km', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'low_stock_threshold');
        }

        $items = $query->latest()->paginate(15)->withQueryString();

        // Inventory health metrics
        $allItems = InventoryItem::where('business_id', $business->id)->get();
        $totalItems = $allItems->count();
        $lowStockCount = $allItems->filter(fn ($i) => $i->current_stock <= $i->low_stock_threshold)->count();
        $totalInventoryValue = $allItems->sum(fn ($i) => (float)$i->current_stock * (float)$i->unit_cost);

        $recentMovements = InventoryMovement::where('business_id', $business->id)
            ->with(['inventoryItem', 'user'])
            ->latest()
            ->take(10)
            ->get();

        return view('business.inventory.index', compact(
            'items',
            'totalItems',
            'lowStockCount',
            'totalInventoryValue',
            'recentMovements'
        ));
    }

    /**
     * Store a new raw material or ingredient.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:150'],
            'name_km' => ['nullable', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $item = DB::transaction(function () use ($business, $validated) {
            $item = InventoryItem::create([
                'business_id' => $business->id,
                'name_en' => $validated['name_en'],
                'name_km' => $validated['name_km'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'unit' => $validated['unit'],
                'current_stock' => $validated['current_stock'],
                'low_stock_threshold' => $validated['low_stock_threshold'],
                'unit_cost' => $validated['unit_cost'],
                'is_active' => true,
            ]);

            // Initial stock movement
            if ((float)$validated['current_stock'] > 0) {
                InventoryMovement::create([
                    'business_id' => $business->id,
                    'inventory_item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'type' => 'in',
                    'quantity' => $validated['current_stock'],
                    'balance_after' => $validated['current_stock'],
                    'cost_per_unit' => $validated['unit_cost'],
                    'reason' => 'Initial opening inventory balance',
                    'created_at' => now(),
                ]);
            }

            return $item;
        });

        return back()->with('success', "Inventory item '{$item->name_en}' created.");
    }

    /**
     * Update inventory item metadata.
     */
    public function update(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:150'],
            'name_km' => ['nullable', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $inventory->update($validated);

        return back()->with('success', "Item '{$inventory->name_en}' updated.");
    }

    /**
     * Manual stock adjustment (stock-in, waste, physical recount).
     */
    public function adjustStock(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:in,out,adjustment,waste'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $stockTransition = DB::transaction(function () use ($business, $inventory, $validated): array {
            $qty = (float) $validated['quantity'];
            $current = (float) $inventory->current_stock;
            $newBalance = $current;
            $delta = $qty;

            if ($validated['type'] === 'in') {
                $newBalance = $current + $qty;
                $delta = $qty;
            } elseif ($validated['type'] === 'out' || $validated['type'] === 'waste') {
                $newBalance = max(0, $current - $qty);
                $delta = -$qty;
            } elseif ($validated['type'] === 'adjustment') {
                // Direct set to new balance
                $newBalance = $qty;
                $delta = $qty - $current;
            }

            $inventory->update(['current_stock' => $newBalance]);

            InventoryMovement::create([
                'business_id' => $business->id,
                'inventory_item_id' => $inventory->id,
                'user_id' => Auth::id(),
                'type' => $validated['type'],
                'quantity' => $delta,
                'balance_after' => $newBalance,
                'cost_per_unit' => $inventory->unit_cost,
                'reason' => $validated['reason'] ?? "Stock adjustment ({$validated['type']})",
                'created_at' => now(),
            ]);

            return [$current, $newBalance];
        });

        app(\App\Services\Notifications\BusinessNotificationService::class)
            ->notifyInventoryStockTransition($inventory->fresh(), $stockTransition[0], $stockTransition[1]);

        return back()->with('success', "Stock updated for '{$inventory->name_en}'.");
    }

    /**
     * Delete an inventory item.
     */
    public function destroy(InventoryItem $inventory): RedirectResponse
    {
        $name = $inventory->name_en;
        $inventory->delete();

        return back()->with('success', "Item '{$name}' removed from inventory.");
    }
}
