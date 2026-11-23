<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Services\Qr\QrCodeService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QrStudioController extends Controller
{
    public function __construct(protected QrCodeService $qrCodeService) {}

    /**
     * Display QR Code Studio with customization and downloads.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $tables = RestaurantTable::with('qrCode')->where('business_id', $business->id)->orderBy('table_number')->get();
        $publicMenuUrl = url('/' . $business->slug);

        $qrSettings = $business->theme_settings['qr'] ?? [
            'color' => '#f97316',
            'background' => '#ffffff',
            'frame' => 'minimal',
            'show_logo' => true,
        ];

        return view('business.qr.index', compact('business', 'tables', 'publicMenuUrl', 'qrSettings'));
    }

    /**
     * Save QR Code customization styles.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'color' => 'required|string|max:20',
            'background' => 'required|string|max:20',
            'frame' => 'required|in:minimal,badge,standee',
            'show_logo' => 'nullable|boolean',
        ]);

        $settings = $business->theme_settings ?? [];
        $settings['qr'] = [
            'color' => $validated['color'],
            'background' => $validated['background'],
            'frame' => $validated['frame'],
            'show_logo' => $request->boolean('show_logo'),
        ];

        $business->update(['theme_settings' => $settings]);

        return back()->with('status', 'QR code branding settings saved successfully!');
    }
}
