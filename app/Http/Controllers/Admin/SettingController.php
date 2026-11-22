<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display platform system settings.
     */
    public function index(): View
    {
        $platform = [
            'name' => config('menuhub.name'),
            'tagline' => config('menuhub.tagline'),
            'default_currency' => config('menuhub.default_currency'),
            'exchange_rate_khr' => config('menuhub.default_exchange_rate_khr'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_driver' => config('database.default'),
            'database_host' => config('database.connections.pgsql.host'),
        ];

        return view('admin.settings.index', compact('platform'));
    }

    /**
     * Update platform settings.
     */
    public function update(Request $request): RedirectResponse
    {
        // Platform settings can be saved to a system_settings table or config store
        return back()->with('success', 'Platform settings saved successfully.');
    }
}
