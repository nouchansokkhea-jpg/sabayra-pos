<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderCheckoutController extends Controller
{
    /**
     * Process checkout submission from the public digital menu cart.
     */
    public function checkout(Request $request, string $business_slug): JsonResponse
    {
        try {
            $decodedSlug = urldecode($business_slug);
            $business = Business::where(function ($query) use ($business_slug, $decodedSlug) {
                $query->where('slug', $business_slug)
                    ->orWhere('slug', $decodedSlug);
            })
                ->where('status', 'active')
                ->firstOrFail();

            TenantContext::set($business);

            $validated = $request->validate([
                'customer_name' => ['required', 'string', 'max:150'],
                'customer_phone' => ['required', 'string', 'max:50'],
                'order_type' => ['required', 'string', 'in:dine_in,takeaway,delivery'],
                'table_number' => ['nullable', 'string', 'max:50'],
                'delivery_address' => ['nullable', 'string', 'max:500'],
                'payment_method' => ['required', 'string', 'in:cash,khqr,card'],
                'notes' => ['nullable', 'string', 'max:500'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'exists:products,id'],
                'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
                'items.*.addons' => ['nullable', 'array'],
                'items.*.addons.*' => ['exists:product_addons,id'],
                'items.*.quantity' => ['required', 'numeric', 'min:1', 'max:100'],
                'items.*.notes' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validated['order_type'] === 'dine_in' && empty($validated['table_number'])) {
                return response()->json(['success' => false, 'error' => 'Please select or enter your table number for dine-in.'], 422);
            }

            if ($validated['order_type'] === 'delivery' && empty($validated['delivery_address'])) {
                return response()->json(['success' => false, 'error' => 'Please provide a delivery address.'], 422);
            }

            // Table resolution
            $tableId = null;
            if (!empty($validated['table_number'])) {
                $table = RestaurantTable::where('business_id', $business->id)
                    ->where('table_number', $validated['table_number'])
                    ->first();
                $tableId = $table?->id;
            }

        $order = DB::transaction(function () use ($business, $validated, $tableId) {
            Business::whereKey($business->id)->lockForUpdate()->first();

            // 1. Resolve or Create Customer
            $customer = Customer::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'phone' => $validated['customer_phone'],
                ],
                [
                    'name' => $validated['customer_name'],
                ]
            );

            // 2. Generate Sequential Order Number scoped to this business
            $orderNumber = Order::generateDailySequenceNumber($business->id, 'order_number', 'ORD', 4);

            // 3. Calculate Accurate Prices from Database
            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $quantity = (float) $itemData['quantity'];

                // Base / Effective price
                $unitPrice = $product->effective_price;
                $variantName = null;

                // Variant adjustment
                if (!empty($itemData['variant_id'])) {
                    $variant = ProductVariant::find($itemData['variant_id']);
                    if ($variant) {
                        $unitPrice += (float) $variant->price_adjustment;
                        $variantName = $variant->name_en;
                    }
                }

                $itemSubtotal = $unitPrice * $quantity;

                // Addons pricing
                $itemAddons = [];
                if (!empty($itemData['addons'])) {
                    foreach ($itemData['addons'] as $addonId) {
                        $addon = ProductAddon::find($addonId);
                        if ($addon) {
                            $addonPrice = (float) $addon->price;
                            $itemSubtotal += ($addonPrice * $quantity);
                            $itemAddons[] = [
                                'addon_id' => $addon->id,
                                'name' => $addon->name_en,
                                'price' => $addonPrice,
                                'quantity' => 1,
                            ];
                        }
                    }
                }

                $subtotal += $itemSubtotal;

                $preparedItems[] = [
                    'product' => $product,
                    'variant_id' => $itemData['variant_id'] ?? null,
                    'product_name' => $product->name_en,
                    'variant_name' => $variantName,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                    'notes' => $itemData['notes'] ?? null,
                    'addons' => $itemAddons,
                ];

                // Deduct finished item inventory if tracked
                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $quantity);
                }
            }

            // 4. Calculate Tax & Total
            $taxRate = (float) ($business->tax_percent ?? 0.00);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $totalAmount = $subtotal + $taxAmount;
            $totalKhr = $business->khrEquivalent($totalAmount);

            // 5. Create Order Record
            $order = Order::create([
                'business_id' => $business->id,
                'order_number' => $orderNumber,
                'order_type' => $validated['order_type'],
                'table_id' => $tableId,
                'customer_id' => $customer->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'delivery_address' => $validated['delivery_address'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => 0.00,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'total_khr' => $totalKhr,
                'currency' => $business->default_currency,
                'exchange_rate_khr' => $business->exchange_rate_khr,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 6. Create Order Items & Addons
            foreach ($preparedItems as $prepItem) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $prepItem['product']->id,
                    'variant_id' => $prepItem['variant_id'],
                    'product_name' => $prepItem['product_name'],
                    'variant_name' => $prepItem['variant_name'],
                    'unit_price' => $prepItem['unit_price'],
                    'quantity' => $prepItem['quantity'],
                    'subtotal' => $prepItem['subtotal'],
                    'notes' => $prepItem['notes'],
                ]);

                foreach ($prepItem['addons'] as $add) {
                    OrderItemAddon::create([
                        'order_item_id' => $orderItem->id,
                        'addon_id' => $add['addon_id'],
                        'name' => $add['name'],
                        'price' => $add['price'],
                        'quantity' => $add['quantity'],
                    ]);
                }
            }

            // 7. Order Status History Initial Entry
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'note' => 'Order placed online by customer.',
                'created_at' => now(),
            ]);

            // Update Customer Stats
            $customer->increment('total_orders');
            $customer->increment('total_spent', $totalAmount);

            // Deduct recipe ingredients automatically if defined
            try {
                (new \App\Services\Inventory\RecipeDeductionService())->deductOrderIngredients($order);
            } catch (\Throwable $deductionErr) {
                \Illuminate\Support\Facades\Log::warning('Recipe deduction error: ' . $deductionErr->getMessage());
            }

            return $order;
        });

        app(\App\Services\Notifications\BusinessNotificationService::class)->notifyOrderEvent($order, 'order.created');

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'redirect_url' => url("/{$business->slug}/order/{$order->order_number}"),
            'message' => 'Your order has been placed successfully!',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'error' => collect($e->errors())->flatten()->first() ?? 'Invalid input data.',
        ], 422);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Order checkout error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Order processing failed: ' . $e->getMessage(),
        ], 500);
    }
}
}
