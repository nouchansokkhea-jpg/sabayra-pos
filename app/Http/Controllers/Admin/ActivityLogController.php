<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of system activity logs.
     */
    public function index(Request $request): View
    {
        $query = Activity::with(['causer', 'subject']);

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%");
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return view('admin.activity-logs.index', compact('logs'));
    }
}
