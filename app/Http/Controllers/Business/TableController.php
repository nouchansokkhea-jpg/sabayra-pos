<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\QrCode;
use App\Models\RestaurantTable;
use App\Services\Qr\QrCodeService;
use App\Services\Tenant\TenantContext;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TableController extends Controller
{
    /**
     * Display restaurant tables and QR codes.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $tables = RestaurantTable::with('qrCode')->orderBy('table_number')->get();
        $branches = BusinessBranch::where('is_active', true)->get();

        // Business main QR code
        $businessQr = QrCode::where('business_id', $business->id)
            ->where('type', 'menu')
            ->first();

        return view('business.tables.index', compact('tables', 'branches', 'business', 'businessQr'));
    }

    /**
     * Store new table and auto-generate QR code.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'branch_id' => ['nullable', 'exists:business_branches,id'],
        ]);

        if (RestaurantTable::where('business_id', $business->id)->where('table_number', $validated['table_number'])->exists()) {
            return back()->with('error', "Table number '{$validated['table_number']}' already exists.");
        }

        $table = RestaurantTable::create([
            'business_id' => $business->id,
            'branch_id' => $validated['branch_id'] ?? null,
            'table_number' => $validated['table_number'],
            'name' => $validated['name'] ?? "Table {$validated['table_number']}",
            'capacity' => $validated['capacity'],
            'status' => 'available',
            'is_active' => true,
        ]);

        // Auto-generate QR code entry
        QrCode::create([
            'business_id' => $business->id,
            'table_id' => $table->id,
            'type' => 'table',
            'code' => "QR-TBL-{$business->slug}-{$table->table_number}",
            'target_url' => QrCodeService::getTableUrl($business, $table->table_number),
        ]);

        return back()->with('success', "Table {$table->table_number} created with instant QR code.");
    }

    /**
     * Update table status (available, occupied, reserved).
     */
    public function update(Request $request, RestaurantTable $table): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:available,occupied,reserved'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $table->update($validated);

        return back()->with('success', "Table {$table->table_number} updated.");
    }

    /**
     * Delete table.
     */
    public function destroy(RestaurantTable $table): RedirectResponse
    {
        $number = $table->table_number;
        $table->delete();

        return back()->with('success', "Table {$number} deleted.");
    }

    /**
     * Download Storefront Master QR as PNG file.
     */
    public function downloadMasterQrPng(): Response
    {
        $business = TenantContext::get();
        $targetUrl = QrCodeService::getMenuUrl($business);
        $png = $this->generateQrPng($targetUrl, 600, [6, 78, 59]);
        $filename = Str::slug($business->name) . "-master-menu-qr.png";

        return response($png)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * View Storefront Master Standee for printing or saving as PDF.
     */
    public function masterStandee(): View
    {
        $business = TenantContext::get();
        $targetUrl = QrCodeService::getMenuUrl($business);
        $label = "Master Digital Menu";

        return view('business.tables.standee', compact('business', 'targetUrl', 'label'));
    }

    /**
     * Download Table QR as PNG file.
     */
    public function downloadTableQrPng(RestaurantTable $table): Response
    {
        $business = TenantContext::get();
        $targetUrl = QrCodeService::getTableUrl($business, $table->table_number);
        $png = $this->generateQrPng($targetUrl, 600, [15, 23, 42]);
        $filename = Str::slug($business->name) . "-table-{$table->table_number}-qr.png";

        return response($png)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * View Table Standee for printing or saving as PDF.
     */
    public function tableStandee(RestaurantTable $table): View
    {
        $business = TenantContext::get();
        $targetUrl = QrCodeService::getTableUrl($business, $table->table_number);
        $label = "Table #{$table->table_number}";

        return view('business.tables.standee', compact('business', 'targetUrl', 'label', 'table'));
    }

    /**
     * Generate high-res QR code PNG binary using GD.
     */
    protected function generateQrPng(string $url, int $size = 600, array $rgb = [15, 23, 42]): string
    {
        $qr = Encoder::encode($url, ErrorCorrectionLevel::M(), 'UTF-8');
        $matrix = $qr->getMatrix();
        $matrixWidth = $matrix->getWidth();
        $matrixHeight = $matrix->getHeight();

        $scale = max(1, (int) floor($size / ($matrixWidth + 8)));
        $margin = 4;
        $imgWidth = ($matrixWidth + ($margin * 2)) * $scale;
        $imgHeight = ($matrixHeight + ($margin * 2)) * $scale;

        $img = imagecreatetruecolor($imgWidth, $imgHeight);
        $white = imagecolorallocate($img, 255, 255, 255);
        $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);

        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $matrixHeight; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $img,
                        ($x + $margin) * $scale,
                        ($y + $margin) * $scale,
                        (($x + $margin + 1) * $scale) - 1,
                        (($y + $margin + 1) * $scale) - 1,
                        $color
                    );
                }
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }
}
