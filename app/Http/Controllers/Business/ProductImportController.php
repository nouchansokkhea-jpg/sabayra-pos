<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    /**
     * Show bulk product import screen.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $categories = Category::where('business_id', $business->id)->get();
        $totalProducts = Product::where('business_id', $business->id)->count();

        return view('business.products.import', compact('business', 'categories', 'totalProducts'));
    }

    /**
     * Download a clean CSV template for bulk importing products.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sabayra_products_import_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Name_English',
                'Name_Khmer',
                'Category',
                'Base_Price_USD',
                'Cost_Price_USD',
                'SKU',
                'Barcode',
                'Description_English',
                'Description_Khmer',
                'Stock_Quantity',
                'Unit'
            ]);

            fputcsv($handle, [
                'Iced Latte',
                'ឡាតេទឹកកក',
                'Coffee',
                '2.50',
                '0.80',
                'COF-LAT-01',
                '8851234567890',
                'Rich espresso with fresh milk and ice',
                'កាហ្វេអេសប្រេសសូជាមួយទឹកដោះគោស្រស់ និងទឹកកក',
                '100',
                'cup'
            ]);

            fputcsv($handle, [
                'Lok Lak Beef with Rice',
                'បាយឡុកឡាក់សាច់គោ',
                'Main Dishes',
                '5.50',
                '2.20',
                'FOOD-LL-01',
                '8859876543210',
                'Tender marinated beef stir-fried with lime pepper dip',
                'សាច់គោប្រឡាក់ទន់ឆាជាមួយទឹកត្រីម្រេចក្រូចឆ្មា',
                '50',
                'plate'
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Parse and import uploaded CSV records.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue;
            }

            $nameEn = trim($row[0]);
            $nameKm = !empty($row[1]) ? trim($row[1]) : null;
            $categoryName = !empty($row[2]) ? trim($row[2]) : 'General';
            $price = !empty($row[3]) ? (float) $row[3] : 0.00;
            $cost = !empty($row[4]) ? (float) $row[4] : 0.00;
            $sku = !empty($row[5]) ? trim($row[5]) : null;
            $barcode = !empty($row[6]) ? trim($row[6]) : null;
            $descEn = !empty($row[7]) ? trim($row[7]) : null;
            $descKm = !empty($row[8]) ? trim($row[8]) : null;
            $stock = !empty($row[9]) ? (float) $row[9] : 0.00;
            $unit = !empty($row[10]) ? trim($row[10]) : 'item';

            // Find or create category
            $category = Category::firstOrCreate(
                ['business_id' => $business->id, 'name_en' => $categoryName],
                ['slug' => Str::slug($categoryName) ?: 'cat-' . rand(100, 999)]
            );

            // Generate unique slug for product
            $baseSlug = Str::slug($nameEn) ?: 'product-' . rand(100, 999);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('business_id', $business->id)->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            Product::create([
                'business_id' => $business->id,
                'category_id' => $category->id,
                'name_en' => $nameEn,
                'name_km' => $nameKm,
                'slug' => $slug,
                'sku' => $sku,
                'barcode' => $barcode,
                'base_price' => $price,
                'cost_price' => $cost,
                'description_en' => $descEn,
                'description_km' => $descKm,
                'track_inventory' => $stock > 0,
                'stock_quantity' => $stock,
                'unit' => $unit,
                'is_available' => true,
            ]);

            $imported++;
        }

        fclose($handle);

        return redirect()->route('business.products.index')
            ->with('status', "Successfully imported {$imported} products from CSV into your catalog!");
    }
}
