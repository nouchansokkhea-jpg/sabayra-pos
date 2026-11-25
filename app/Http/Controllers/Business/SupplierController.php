<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Display suppliers directory and purchase orders.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        $tab = $request->query('tab', 'suppliers');

        $suppliers = Supplier::where('business_id', $business->id)->latest()->get();
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.inventoryItem'])
            ->where('business_id', $business->id)
            ->latest()
            ->get();
        $inventoryItems = InventoryItem::where('business_id', $business->id)->get();

        return view('business.suppliers.index', compact('business', 'suppliers', 'purchaseOrders', 'inventoryItems', 'tab'));
    }

    /**
     * Store a new vendor / supplier.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $validated['business_id'] = $business->id;

        Supplier::create($validated);

        return back()->with('status', 'Supplier registered successfully!');
    }

    /**
     * Create a purchase order.
     */
    public function storePo(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|uuid|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $poNumber = DB::transaction(function () use ($business, $validated) {
            Business::whereKey($business->id)->lockForUpdate()->first();

            $poNumber = PurchaseOrder::generateDailySequenceNumber($business->id, 'po_number', 'PO', 3);

            $totalCost = 0.0;
            foreach ($validated['items'] as $item) {
                $totalCost += (float) $item['quantity'] * (float) $item['unit_cost'];
            }

            $po = PurchaseOrder::create([
                'business_id' => $business->id,
                'supplier_id' => $validated['supplier_id'],
                'po_number' => $poNumber,
                'status' => 'draft',
                'total_cost' => $totalCost,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => (float) $item['quantity'] * (float) $item['unit_cost'],
                ]);
            }

            return $poNumber;
        });

        return back()->with('status', "Purchase Order {$poNumber} created successfully!");
    }

    /**
     * Update purchase order status (sent, received, cancelled).
     */
    public function updatePoStatus(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($po->business_id !== $business->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,received,cancelled',
        ]);

        $po->update([
            'status' => $validated['status'],
            'received_at' => $validated['status'] === 'received' ? now() : $po->received_at,
        ]);

        // If marked received, automatically add stock to inventory
        if ($validated['status'] === 'received') {
            foreach ($po->items as $item) {
                if ($item->inventoryItem) {
                    $item->inventoryItem->increment('current_stock', $item->quantity);
                }
            }
        }

        return back()->with('status', "Purchase Order status updated to {$validated['status']}.");
    }
}
