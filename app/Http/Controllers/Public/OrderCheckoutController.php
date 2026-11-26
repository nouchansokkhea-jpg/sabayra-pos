<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Services\Inventory\RecipeDeductionService;
use App\Services\Notifications\BusinessNotificationService;
use App\Services\Payment\AbaPayWayService;
use App\Services\Payment\BakongKhqrService;
use App\Services\Payment\PaymentGatewayVisibilityService;
use App\Services\Pricing\OrderPricingCalculator;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderCheckoutController extends Controller
{
    public function __construct(
        private OrderPricingCalculator $pricingCalculator,
        private PaymentGatewayVisibilityService $visibilityService,
        private AbaPayWayService $abaPayWayService,
        private BakongKhqrService $bakongKhqrService,
    ) {
    }

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
                'payment_method' => ['required', 'string', 'in:cash,cod,khqr,bakong_khqr,aba_payway,bank_transfer,static_qr,card'],
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

            // Enforce active & verified gateway rules strictly at checkout
            if (! $this->visibilityService->isMethodAllowedForCheckout($business, $validated['payment_method'], $validated['order_type'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'The selected payment method is disabled, unverified, or not eligible for this order type.',
                ], 422);
            }

            $normalizedMethod = $this->visibilityService->normalizeCheckoutMethod(
                $business,
                $validated['payment_method'],
                $validated['order_type']
            );
            if ($normalizedMethod === null) {
                return response()->json(['success' => false, 'error' => 'Unsupported payment method.'], 422);
            }

            $config = BusinessPaymentConfig::resolveForBusiness($business);

            // Table resolution
            $tableId = null;
            if (! empty($validated['table_number'])) {
                $table = RestaurantTable::where('business_id', $business->id)
                    ->where('table_number', $validated['table_number'])
                    ->first();
                $tableId = $table?->id;
            }

            $order = DB::transaction(function () use ($business, $validated, $tableId, $normalizedMethod, $config): Order {
                Business::whereKey($business->id)->lockForUpdate()->first();

                $customer = Customer::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'phone' => $validated['customer_phone'],
                    ],
                    [
                        'name' => $validated['customer_name'],
                    ]
                );

                $orderNumber = Order::generateDailySequenceNumber($business->id, 'order_number', 'ORD', 4);

                $subtotal = 0.00;
                $preparedItems = [];

                foreach ($validated['items'] as $itemData) {
                    $product = Product::where('business_id', $business->id)->findOrFail($itemData['product_id']);
                    $quantity = (float) $itemData['quantity'];

                    $unitPrice = (float) $product->effective_price;
                    $variantName = null;

                    if (! empty($itemData['variant_id'])) {
                        $variant = ProductVariant::where('product_id', $product->id)->find($itemData['variant_id']);
                        if ($variant) {
                            $unitPrice += (float) $variant->price_adjustment;
                            $variantName = $variant->name_en;
                        }
                    }

                    $itemSubtotal = $unitPrice * $quantity;
                    $itemAddons = [];

                    if (! empty($itemData['addons'])) {
                        foreach ($itemData['addons'] as $addonId) {
                            $addon = ProductAddon::where('business_id', $business->id)->find($addonId);
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

                    if ($product->track_inventory) {
                        $product->decrement('stock_quantity', $quantity);
                    }
                }

                // Authoritative Tax & Service Charge calculation + Order Snapshot
                $pricing = $this->pricingCalculator->calculate($business, $subtotal, 0.00);
                $initialPaymentStatus = $normalizedMethod === 'cod' ? 'pending_collection' : 'unpaid';

                $order = Order::create([
                    'business_id' => $business->id,
                    'order_number' => $orderNumber,
                    'order_type' => $validated['order_type'],
                    'table_id' => $tableId,
                    'customer_id' => $customer->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'subtotal' => $pricing['gross_subtotal'],
                    'discount_amount' => $pricing['discount_amount'],
                    'service_charge_enabled' => $pricing['service_charge_enabled'],
                    'service_charge_rate' => $pricing['service_charge_rate'],
                    'service_charge_amount' => $pricing['service_charge_amount'],
                    'tax_name' => $pricing['tax_name'],
                    'tax_rate' => $pricing['tax_rate'],
                    'tax_inclusive' => $pricing['tax_inclusive'],
                    'tax_amount' => $pricing['tax_amount'],
                    'total_amount' => $pricing['total_amount'],
                    'total_khr' => $pricing['total_khr'],
                    'currency' => $pricing['currency'],
                    'exchange_rate_khr' => $pricing['exchange_rate_khr'],
                    'status' => 'pending',
                    'payment_status' => $initialPaymentStatus,
                    'payment_method' => $normalizedMethod,
                    'notes' => $validated['notes'] ?? null,
                ]);

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

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => 'pending',
                    'note' => 'Order placed online by customer.',
                    'created_at' => now(),
                ]);

                $customer->increment('total_orders');
                $customer->increment('total_spent', $pricing['total_amount']);

                try {
                    (new RecipeDeductionService())->deductOrderIngredients($order);
                } catch (\Throwable $deductionErr) {
                    Log::warning('Recipe deduction error: ' . $deductionErr->getMessage());
                }

                // Provision gateway / tracking transaction inside the same transaction
                $this->provisionPaymentTransaction($business, $order, $normalizedMethod, $config);

                return $order;
            });

            app(BusinessNotificationService::class)->notifyOrderEvent($order, 'order.created');

            $proofToken = $this->visibilityService->generateOrderProofToken($order);
            $redirectUrl = route('public.order-status', [
                'business_slug' => $business->slug,
                'order_number' => $order->order_number,
                'token' => $proofToken,
            ]);

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'proof_token' => $proofToken,
                'redirect_url' => $redirectUrl,
                'message' => 'Your order has been placed successfully!',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => collect($e->errors())->flatten()->first() ?? 'Invalid input data.',
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Order checkout error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Order processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create the gateway transaction or pending collection/manual reference for the new order.
     */
    private function provisionPaymentTransaction(
        Business $business,
        Order $order,
        string $method,
        BusinessPaymentConfig $config
    ): void {
        if ($method === 'khqr') {
            $res = $this->bakongKhqrService->createOrderTransaction($business, $order, $config);
            if (! $res['ok']) {
                throw new \RuntimeException($res['error'] ?? 'Unable to create Bakong KHQR payment session.');
            }

            return;
        }

        if ($method === 'aba_payway') {
            if ($config->hasAbaMerchantApiCredentials()) {
                $res = $this->abaPayWayService->createOrderTransaction($order, $config);
                if (! $res['ok']) {
                    throw new \RuntimeException($res['error'] ?? 'Unable to initialize ABA PayWay dynamic payment.');
                }

                return;
            }

            if ($config->hasValidAbaPaywayLink()) {
                $reference = 'ABALINK-' . strtoupper(Str::random(10));
                PaymentTransaction::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'order_id' => $order->id,
                    'gateway' => 'aba_payway',
                    'reference_number' => $reference,
                    'checkout_url' => $config->aba_payway_link,
                    'amount' => $order->currency === 'KHR' ? $order->total_khr : $order->total_amount,
                    'currency' => $order->currency,
                    'status' => 'pending',
                    'idempotency_key' => "aba_link:{$order->id}:{$reference}",
                    'notes' => 'Customer redirected to configured merchant ABA PayWay Link.',
                ]);
                $order->forceFill(['payment_reference' => $reference])->save();
            }

            return;
        }

        if ($method === 'cod') {
            $reference = 'COD-' . $order->order_number;
            PaymentTransaction::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'order_id' => $order->id,
                'gateway' => 'cod',
                'reference_number' => $reference,
                'amount' => $order->currency === 'KHR' ? $order->total_khr : $order->total_amount,
                'currency' => $order->currency,
                'status' => 'pending_collection',
                'idempotency_key' => "cod:{$order->id}",
            ]);
            $order->forceFill(['payment_reference' => $reference])->save();
        }
    }
}
