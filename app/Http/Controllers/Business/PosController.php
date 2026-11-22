<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
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
use App\Models\RestaurantTable;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    /**
     * Display the Touch POS Terminal interface.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        /** @var \App\Models\User $user */
        $user = $request->user() ?? Auth::user();

        // Check for active open session
        $activeSession = PosSession::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        $branches = BusinessBranch::where('is_active', true)->get();
        $tables = RestaurantTable::where('is_active', true)->orderBy('table_number')->get();

        $categories = Category::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_available', true)->with(['variants', 'addons']);
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

        return view('business.pos.index', compact(
            'business',
            'activeSession',
            'branches',
            'tables',
            'categories',
            'recentTransactions'
        ));
    }

    /**
     * Open a new POS shift / register session.
     */
    public function openSession(Request $request): RedirectResponse
    {
        $business = TenantContext::get();
        /** @var \App\Models\User $user */
        $user = $request->user() ?? Auth::user();

        // Ensure no active open session already exists
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
            : ($difference > 0 ? "Cash over by $" . number_format($difference, 2) : "Cash short by $" . number_format(abs($difference), 2));

        return redirect()->route('business.pos.index')->with('success', "POS register closed. {$diffMessage}");
    }

    /**
     * Immediate in-person checkout from the Touch POS Terminal.
     */
    public function checkout(Request $request): JsonResponse
    {
        $business = TenantContext::get();
        /** @var \App\Models\User $user */
        $user = $request->user() ?? Auth::user();

        // 1. Verify active POS session
        $activeSession = PosSession::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$activeSession) {
            return response()->json(['error' => 'No active POS register session is open. Please open a shift first.'], 422);
        }

        $validated = $request->validate([
            'order_type' => ['required', 'string', 'in:dine_in,takeaway,delivery'],
            'table_number' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'in:cash,khqr,card'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.addons' => ['nullable', 'array'],
            'items.*.addons.*' => ['exists:product_addons,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1', 'max:500'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $customerName = !empty($validated['customer_name']) ? $validated['customer_name'] : 'Walk-in Guest';
        $customerPhone = !empty($validated['customer_phone']) ? $validated['customer_phone'] : null;

        // Table resolution
        $tableId = null;
        if ($validated['order_type'] === 'dine_in' && !empty($validated['table_number'])) {
            $table = RestaurantTable::where('business_id', $business->id)
                ->where('table_number', $validated['table_number'])
                ->first();
            $tableId = $table?->id;
        }

        $result = DB::transaction(function () use ($business, $activeSession, $user, $validated, $customerName, $customerPhone, $tableId) {
            // Customer
            $customerId = null;
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

            // Sequential Order Number
            $todayDate = Carbon::now()->format('Ymd');
            $dailyCount = Order::where('business_id', $business->id)
                ->whereDate('created_at', Carbon::today())
                ->count() + 1;
            $orderNumber = sprintf('POS-%s-%04d', $todayDate, $dailyCount);

            // Calculate item totals
            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $quantity = (float) $itemData['quantity'];

                $unitPrice = $product->effective_price;
                $variantName = null;

                if (!empty($itemData['variant_id'])) {
                    $variant = ProductVariant::find($itemData['variant_id']);
                    if ($variant) {
                        $unitPrice += (float) $variant->price_adjustment;
                        $variantName = $variant->name_en;
                    }
                }

                $itemSubtotal = $unitPrice * $quantity;

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

                // Deduct inventory if tracked
                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $quantity);
                }
            }

            // Discounts and Tax
            $discount = (float) ($validated['discount_amount'] ?? 0.00);
            $taxable = max(0, $subtotal - $discount);
            $taxRate = (float) ($business->tax_percent ?? 0.00);
            $taxAmount = round($taxable * ($taxRate / 100), 2);
            $totalAmount = $taxable + $taxAmount;
            $totalKhr = $business->khrEquivalent($totalAmount);

            // Change calculation
            $amountTendered = (float) ($validated['amount_tendered'] ?? $totalAmount);
            $changeGiven = max(0.00, round($amountTendered - $totalAmount, 2));

            // Create Order
            $order = Order::create([
                'business_id' => $business->id,
                'branch_id' => $activeSession->branch_id,
                'order_number' => $orderNumber,
                'order_type' => $validated['order_type'],
                'table_id' => $tableId,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'total_khr' => $totalKhr,
                'currency' => $business->default_currency,
                'exchange_rate_khr' => $business->exchange_rate_khr,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create Order Items
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

            // Initial status audit
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'from_status' => null,
                'to_status' => 'completed',
                'note' => 'POS direct sale completed and paid.',
                'created_at' => now(),
            ]);

            // Sequential Receipt Number
            $receiptCount = PosTransaction::where('business_id', $business->id)->count() + 1;
            $receiptNumber = sprintf('REC-%s-%05d', $todayDate, $receiptCount);

            // Create POS Transaction
            $posTransaction = PosTransaction::create([
                'pos_session_id' => $activeSession->id,
                'business_id' => $business->id,
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'amount_khr' => $totalKhr,
                'payment_method' => $validated['payment_method'],
                'amount_tendered' => $amountTendered,
                'change_given' => $changeGiven,
                'receipt_number' => $receiptNumber,
            ]);

            if (isset($customer)) {
                $customer->increment('total_orders');
                $customer->increment('total_spent', $totalAmount);
            }

            // Deduct recipe ingredients automatically
            (new \App\Services\Inventory\RecipeDeductionService())->deductOrderIngredients($order);

            return [
                'order' => $order,
                'transaction' => $posTransaction,
                'change_usd' => $changeGiven,
                'change_khr' => $business->khrEquivalent($changeGiven),
                'total_usd' => $totalAmount,
                'total_khr' => $totalKhr,
            ];
        });

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
