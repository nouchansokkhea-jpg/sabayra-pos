<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display Platform Business Categories & Sector Presets.
     */
    public function index(Request $request): View
    {
        $businessTypes = config('menuhub.business_types', []);

        $typeCounts = Business::select('business_type', DB::raw('count(*) as count'))
            ->groupBy('business_type')
            ->pluck('count', 'business_type')
            ->toArray();

        $activeCounts = Business::where('status', 'active')
            ->select('business_type', DB::raw('count(*) as count'))
            ->groupBy('business_type')
            ->pluck('count', 'business_type')
            ->toArray();

        return view('admin.categories.index', compact('businessTypes', 'typeCounts', 'activeCounts'));
    }
}
