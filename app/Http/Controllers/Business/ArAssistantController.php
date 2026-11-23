<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\ArAsset;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArAssistantController extends Controller
{
    /**
     * Display AR Table Cards management.
     */
    public function arCards(): View
    {
        $business = TenantContext::get();
        $tables = RestaurantTable::with('qrCode')->where('business_id', $business->id)->get();
        $featuredProducts = Product::where('business_id', $business->id)->where('is_featured', true)->get();

        return view('business.ar.cards', compact('business', 'tables', 'featuredProducts'));
    }

    /**
     * Display 3D Scan Assistant and GLB model previews.
     */
    public function scanAssistant(): View
    {
        $business = TenantContext::get();
        $assets = ArAsset::with('product')->where('business_id', $business->id)->latest()->get();
        $products = Product::where('business_id', $business->id)->get();

        return view('business.ar.scan_assistant', compact('business', 'assets', 'products'));
    }

    /**
     * Register a 3D asset link.
     */
    public function storeAsset(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'product_id' => 'nullable|uuid|exists:products,id',
            'asset_url' => 'required|url',
            'format' => 'required|in:glb,gltf,usdz',
        ]);

        $validated['business_id'] = $business->id;
        $validated['is_active'] = true;

        ArAsset::create($validated);

        return back()->with('status', '3D model registered successfully!');
    }
}
