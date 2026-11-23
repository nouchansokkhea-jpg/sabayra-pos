<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\Order;
use App\Models\Printer;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    /**
     * Display registered receipt and order printers and quick print recent orders.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $printers = Printer::with('branch')
            ->where('business_id', $business->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $branches = BusinessBranch::where('business_id', $business->id)->get();

        // Section 2.5: Quick Print — Recent Orders (Real, tenant-scoped orders)
        $recentOrders = Order::with(['table', 'items'])
            ->where('business_id', $business->id)
            ->latest()
            ->take(8)
            ->get();

        return view('business.printers.index', compact('business', 'printers', 'branches', 'recentOrders'));
    }

    /**
     * Register a new thermal, ESC/POS, or browser printer.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'printer_type' => 'nullable|in:receipt,kitchen,label,barcode,general',
            'connection_type' => 'required|in:network,usb,bluetooth,browser',
            'printing_purpose' => 'nullable|in:receipt,kitchen,both,customer_receipts,kitchen_tickets',
            'paper_size' => 'required|in:58mm,80mm,a4',
            'ip_address' => 'nullable|string|max:50',
            'port' => 'nullable|integer',
            'branch_id' => 'nullable|uuid|exists:business_branches,id',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['business_id'] = $business->id;
        $validated['printer_type'] = $validated['printer_type'] ?? 'receipt';
        $validated['printing_purpose'] = $validated['printing_purpose'] ?? 'both';
        $validated['port'] = $validated['port'] ?? 9100;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        if ($validated['is_default']) {
            Printer::where('business_id', $business->id)->update(['is_default' => false]);
        }

        Printer::create($validated);

        return redirect()->route('business.printers.index')->with('status', 'Printer registered successfully!');
    }

    /**
     * Update an existing printer configuration.
     */
    public function update(Request $request, Printer $printer): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($printer->business_id !== $business->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'printer_type' => 'required|in:receipt,kitchen,label,barcode,general',
            'connection_type' => 'required|in:network,usb,bluetooth,browser',
            'printing_purpose' => 'required|in:receipt,kitchen,both,customer_receipts,kitchen_tickets',
            'paper_size' => 'required|in:58mm,80mm,a4',
            'ip_address' => 'nullable|string|max:50',
            'port' => 'nullable|integer',
            'branch_id' => 'nullable|uuid|exists:business_branches,id',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['port'] = $validated['port'] ?? 9100;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($validated['is_default']) {
            Printer::where('business_id', $business->id)
                ->where('id', '!=', $printer->id)
                ->update(['is_default' => false]);
        }

        $printer->update($validated);

        return redirect()->route('business.printers.index')->with('status', "Printer \"{$printer->name}\" updated successfully!");
    }

    /**
     * Delete a printer.
     */
    public function destroy(Printer $printer): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($printer->business_id !== $business->id, 403);

        $printerName = $printer->name;
        $printer->delete();

        return redirect()->route('business.printers.index')->with('status', "Printer \"{$printerName}\" removed.");
    }

    /**
     * Render a real test slip layout for printing and record last_used_at.
     */
    public function testPrint(Printer $printer): View
    {
        $business = TenantContext::get();
        abort_if($printer->business_id !== $business->id, 403);

        $printer->update(['last_used_at' => now()]);

        return view('business.printers.test_slip', compact('printer', 'business'));
    }
}
