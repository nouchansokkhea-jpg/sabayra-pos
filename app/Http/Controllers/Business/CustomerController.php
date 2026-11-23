<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display customer directory and CRM.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        $search = $request->query('search');

        $query = Customer::where('business_id', $business->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('total_spent')->paginate(15);

        $stats = [
            'total_customers' => Customer::where('business_id', $business->id)->count(),
            'total_spent' => (float) Customer::where('business_id', $business->id)->sum('total_spent'),
            'avg_order_value' => (float) Order::where('business_id', $business->id)->where('payment_status', 'paid')->avg('total_amount'),
        ];

        return view('business.customers.index', compact('business', 'customers', 'search', 'stats'));
    }

    /**
     * Show single customer profile and purchase history.
     */
    public function show(Customer $customer): View
    {
        $business = TenantContext::get();
        abort_if($customer->business_id !== $business->id, 403);

        $orders = Order::where('business_id', $business->id)
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                    ->orWhere('customer_phone', $customer->phone);
            })
            ->latest()
            ->take(20)
            ->get();

        return view('business.customers.show', compact('business', 'customer', 'orders'));
    }

    /**
     * Update customer notes or details.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($customer->business_id !== $business->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customer->update($validated);

        return back()->with('status', 'Customer profile updated successfully!');
    }
}
