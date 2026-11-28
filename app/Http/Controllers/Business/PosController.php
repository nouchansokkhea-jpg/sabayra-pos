<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\BusinessPaymentConfig;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\OrderStatusHistory;
use App\Models\PosSession;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Services\Business\CategoryExperienceService;
use App\Services\Inventory\RecipeDeductionService;
use App\Services\Notifications\BusinessNotificationService;
use App\Services\Payment\PaymentGatewayVisibilityService;
use App\Services\Pricing\OrderPricingCalculator;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct(
        private OrderPricingCalculator $pricingCalculator,
        private PaymentGatewayVisibilityService $visibilityService,
        private CategoryExperienceService $categoryExperience,
    ) {
    }

    /**
     * Display the category-adapted Touch POS Terminal interface.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        /** @var \App\Models\User $user */
        $user = $request->user() ?? Auth::user();

        $activeSession = PosSession::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        $branches = BusinessBranch::where('business_id', $business->id)->where('is_active', true)->get();
        $tables = RestaurantTable::where('business_id', $business->id)->where('is_active', true)->orderBy('table_number')->get();

        $categories = Category::where('business_id', $business->id)
            ->where('is_active', true)
            ->with(['products' => function ($query) use ($business): void {
                $query->where('business_id', $business->id)
                    ->where('is_available', true)
                    ->with(['variants', 'addons']);
            }])
            ->orderBy('sort_order')
            ->get();

        $recentTransactions = collect();
        if ($activeSession) {
            $recentTransactions = PosTransaction::where('pos_session_id', $activeSession->id)
                ->with('order')
                ->latest()
                ->take(10)
                ->get();
        }

        $paymentConfig = BusinessPaymentConfig::resolveForBusiness($business);
        $paymentMethods = $this->visibilityService->resolveCheckoutMethods($business);
        $categoryMeta = $this->categoryExperience->meta($business);
        $posConfig = $this->categoryExperience->posConfig($business);
        $terminology = $this->categoryExperience->terminology($business);

        $staffMembers = collect();
        $upcomingBookings = collect();
        if (! empty($posConfig['show_service_booking'])) {
            $staffMembers = BusinessMember::with('user')
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->get();

            $upcomingBookings = Reservation::with(['serviceProduct', 'staffMember.user'])
                ->where('business_id', $business->id)
                ->where('booking_type', 'service')
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('reservation_date', '>=', now()->toDateString())
                ->orderBy('reservation_date')
                ->orderBy('reservation_time')
                ->take(15)
                ->get();
        }

        return view('business.pos.index', compact(
            'business',
            'activeSession',
            'branches',
            'tables',
            'categories',
            'recentTransactions',
            'paymentConfig',
            'paymentMethods',
            'categoryMeta',
            'posConfig',
            'terminology',
            'staffMembers',
            'upcomingBookings'
        ));
    }

    /**
     * Look up a product or variant strictly within the active tenant by barcode or SKU.
     */
    public function lookupBarcode(Request $request): JsonResponse
    {
        $business = TenantContext::get();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
        ]);

        $code = trim($validated['code']);

        $product = Product::where('business_id', $business->id)
            ->where('is_available', true)
            ->where(function ($q) use ($code): void {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->with(['variants', 'addons'])
            ->first();

        $matchedVariant = null;

        if (! $product) {
            $matchedVariant = ProductVariant::query()
                ->where(function ($q) use ($code): void {
                    $q->where('barcode', $code)->orWhere('sku', $code);
                })
                ->whereHas('product', function ($q) use ($business): void {
                    $q->where('business_id', $business->id)->where('is_available', true);
                })
                ->with(['product.variants', 'product.addons'])
                ->first();

            $product = $matchedVariant?->product;
        }

        if (! $product) {
            return response()->json([
                'found' => false,
                'message' => "No active item matches barcode or SKU '{$code}'.",
            ], 404);
        }

        return response()->json([
            'found' => true,
            'product' => $product,
            'variant' => $matchedVariant,
        ]);
    }

    /**
     * Open a new POS shift / register session.
     */
    public function openSession(Request $request): RedirectResponse
    {
        $business = TenantContext::get();
        /** @var \App\Models\User $user */
        $user = $request->user() ?? Auth::user();

        $existing = PosSession::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return back()->with('error', 'You already have an active POS shift session open.');
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'exists:business_branches,id'],
        ]);

        PosSession::create([
            'business_id' => $business->id,
            'branch_id' => $validated['branch_id'] ?? null,
            'user_id' => $user->id,
            'opening_cash' => $validated['opening_cash'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return redirect()->route('business.pos.index')->with('success', 'POS register shift opened successfully.');
    }

    /**
     * Close the active POS session and reconcile cash drawer.
     */
    public function closeSession(Request $request, PosSession $session): RedirectResponse
    {
        $business = TenantContext::get();

        if ($session->business_id !== $business->id || $session->status !== 'open') {
            abort(403, 'Invalid or already closed POS session.');
        }

        $validated = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $cashSales = (float) $session->transactions()->where('payment_method', 'cash')->sum('amount');
        $expectedCash = (float) $session->opening_cash + $cashSales;

        $session->update([
            'closing_cash' => $validated['closing_cash'],
            'expected_cash' => $expectedCash,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $difference = (float) $validated['closing_cash'] - $expectedCash;
        $diffMessage = $difference === 0.0
            ? 'Cash drawer matched exactly!'
            : ($difference > 0 ? 'Cash over by $' . number_format($difference, 2) : 'Cash short by $' . number_format(abs($difference), 2));

        return redirect()->route('business.pos.index')->with('success', "POS register closed. {$diffMessage}");
    }

    /**
     * Immediate in-person checkout from the Touch POS Terminal using unified pricing rules.
     */
    public function checkout(Request $request): JsonResponse
    {
        $business = TenantContext::get();
        /** @var \App\Models\User $user */
        $user = $request->user() ?? Auth::user();

        $activeSession = PosSession::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $activeSession) {
            return response()->json(['error' => 'No active POS register session is open. Please open a shift first.'], 422);
        }

        $validated = $request->validate([
            'order_type' => ['required', 'string', 'in:dine_in,takeaway,delivery,service'],
            'table_number' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'assigned_staff_id' => ['nullable', 'exists:business_members,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'scheduled_for' => ['nullable', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,cod,khqr,bakong_khqr,aba_payway,bank_transfer,static_qr,card'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'pos_metadata' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.addons.*' => ['exists:product_addons,id'],
            'items.*.options' => ['nullable', 'array'],
            'items.*.quantity' => ['required', 'numeric', 'min:1', 'max:500'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $paymentMethod = $validated['payment_method'] === 'bakong_khqr' ? 'khqr' : $validated['payment_method'];
        $customerName = ! empty($validated['customer_name']) ? $validated['customer_name'] : 'Walk-in Guest';
        $customerPhone = ! empty($validated['customer_phone']) ? $validated['customer_phone'] : null;

        $assignedStaff = null;
        if (! empty($validated['assigned_staff_id'])) {
            $assignedStaff = BusinessMember::where('business_id', $business->id)
                ->find($validated['assigned_staff_id']);
        }

        $linkedReservation = null;
        if (! empty($validated['reservation_id'])) {
            $linkedReservation = Reservation::where('business_id', $business->id)
                ->find($validated['reservation_id']);
        }

        $tableId = null;
        if ($validated['order_type'] === 'dine_in' && ! empty($validated['table_number'])) {
            $table = RestaurantTable::where('business_id', $business->id)
                ->where('table_number', $validated['table_number'])
                ->first();
            $tableId = $table?->id;
        }

        $result = DB::transaction(function () use ($business, $activeSession, $user, $validated, $paymentMethod, $customerName, $customerPhone, $tableId, $assignedStaff, $linkedReservation): array {
            Business::whereKey($business->id)->lockForUpdate()->first();

            $customerId = null;
            $customer = null;
            if ($customerPhone) {
                $customer = Customer::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'phone' => $customerPhone,
                    ],
                    [
                        'name' => $customerName,
                    ]
                );
                $customerId = $customer->id;
            }

            $orderNumber = Order::generateDailySequenceNumber($business->id, 'order_number', 'POS', 4);

            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::where('business_id', $business->id)->findOrFail($itemData['product_id']);
                $quantity = (float) $itemData['quantity'];

                $unitPrice = (float) $product->effective_price;
                $variantName = null;
                $variant = null;

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

                $options = is_array($itemData['options'] ?? null) ? $itemData['options'] : [];
                $optionParts = [];
                foreach (['temp', 'sugar', 'ice', 'size', 'color'] as $optKey) {
                    if (! empty($options[$optKey])) {
                        $optionParts[] = (string) $options[$optKey];
                    }
                }
                $noteText = trim((string) ($itemData['notes'] ?? ''));
                if ($optionParts !== []) {
                    $optionSummary = implode(' · ', $optionParts);
                    $noteText = $noteText !== '' ? "{$optionSummary} — {$noteText}" : $optionSummary;
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
                    'notes' => $noteText !== '' ? $noteText : null,
                    'options_json' => $options !== [] ? $options : null,
                    'addons' => $itemAddons,
                ];

                if ($variant && (float) $variant->stock_quantity > 0) {
                    $variant->decrement('stock_quantity', min($quantity, (float) $variant->stock_quantity));
                }

                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $quantity);
                }
            }

            $discountInput = (float) ($validated['discount_amount'] ?? 0.00);
            $pricing = $this->pricingCalculator->calculate($business, $subtotal, $discountInput);

            $amountTendered = (float) ($validated['amount_tendered'] ?? $pricing['total_amount']);
            $changeGiven = max(0.00, round($amountTendered - $pricing['total_amount'], 2));
            $now = now();

            $isCodDeferred = $paymentMethod === 'cod';
            $orderStatus = $isCodDeferred ? 'confirmed' : 'completed';
            $paymentStatus = $isCodDeferred ? 'pending_collection' : 'paid';

            $order = Order::create([
                'business_id' => $business->id,
                'branch_id' => $activeSession->branch_id,
                'order_number' => $orderNumber,
                'order_type' => $validated['order_type'],
                'table_id' => $tableId,
                'customer_id' => $customerId,
                'assigned_staff_id' => $assignedStaff?->id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'scheduled_for' => $validated['scheduled_for'] ?? null,
                'pos_metadata' => $validated['pos_metadata'] ?? null,
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
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'payment_verified_at' => $isCodDeferred ? null : $now,
                'payment_verified_by' => $isCodDeferred ? null : $user->id,
                'paid_at' => $isCodDeferred ? null : $now,
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
                    'options_json' => $prepItem['options_json'],
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
                'user_id' => $user->id,
                'from_status' => null,
                'to_status' => $orderStatus,
                'note' => $isCodDeferred ? 'POS delivery order recorded with COD pending collection.' : 'POS direct sale completed and paid.',
                'created_at' => $now,
            ]);

            $receiptNumber = PosTransaction::generateDailySequenceNumber($business->id, 'receipt_number', 'REC', 5);

            $posTransaction = PosTransaction::create([
                'pos_session_id' => $activeSession->id,
                'business_id' => $business->id,
                'order_id' => $order->id,
                'amount' => $pricing['total_amount'],
                'amount_khr' => $pricing['total_khr'],
                'payment_method' => $paymentMethod,
                'amount_tendered' => $amountTendered,
                'change_given' => $changeGiven,
                'receipt_number' => $receiptNumber,
            ]);

            if ($customer) {
                $customer->increment('total_orders');
                $customer->increment('total_spent', $pricing['total_amount']);
            }

            if ($linkedReservation) {
                $linkedReservation->update([
                    'status' => 'completed',
                    'payment_status' => $isCodDeferred ? 'pending' : 'paid',
                ]);
            }

            (new RecipeDeductionService())->deductOrderIngredients($order);

            return [
                'order' => $order,
                'transaction' => $posTransaction,
                'change_usd' => $changeGiven,
                'change_khr' => $business->khrEquivalent($changeGiven),
                'total_usd' => $pricing['total_amount'],
                'total_khr' => $pricing['total_khr'],
            ];
        });

        $notificationService = app(BusinessNotificationService::class);
        $notificationService->notifyOrderEvent($result['order'], 'order.created');

        if ($result['order']->payment_status === 'paid') {
            $notificationService->notifyOrderEvent($result['order'], 'order.payment_received');
        }

        return response()->json([
            'success' => true,
            'order_id' => $result['order']->id,
            'order_number' => $result['order']->order_number,
            'receipt_number' => $result['transaction']->receipt_number,
            'receipt_url' => route('business.orders.receipt', $result['order']),
            'total_usd' => $result['total_usd'],
            'total_khr' => $result['total_khr'],
            'change_usd' => $result['change_usd'],
            'change_khr' => $result['change_khr'],
            'message' => 'Payment settled successfully.',
        ]);
    }
}
