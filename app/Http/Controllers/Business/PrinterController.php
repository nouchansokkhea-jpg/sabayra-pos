<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\Printer;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    /**
     * Display registered receipt and order printers.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $printers = Printer::with('branch')->where('business_id', $business->id)->get();
        $branches = BusinessBranch::where('business_id', $business->id)->get();

        return view('business.printers.index', compact('business', 'printers', 'branches'));
    }

    /**
     * Register a new thermal or ESC/POS printer.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'connection_type' => 'required|in:network,usb,bluetooth',
            'paper_size' => 'required|in:58mm,80mm,a4',
            'ip_address' => 'nullable|string|max:50',
            'port' => 'nullable|integer',
            'branch_id' => 'nullable|uuid|exists:business_branches,id',
            'is_default' => 'nullable|boolean',
        ]);

        $validated['business_id'] = $business->id;
        $validated['port'] = $validated['port'] ?? 9100;
        $validated['is_default'] = $request->boolean('is_default');

        if ($validated['is_default']) {
            Printer::where('business_id', $business->id)->update(['is_default' => false]);
        }

        Printer::create($validated);

        return back()->with('status', 'Printer registered successfully!');
    }

    /**
     * Delete a printer.
     */
    public function destroy(Printer $printer): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($printer->business_id !== $business->id, 403);

        $printer->delete();

        return back()->with('status', 'Printer removed.');
    }
}
