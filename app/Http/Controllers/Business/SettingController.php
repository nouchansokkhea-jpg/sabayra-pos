<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Display business settings.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $businessTypes = config('menuhub.business_types');
        $currencies = config('menuhub.currencies');

        return view('business.settings.index', compact('business', 'businessTypes', 'currencies'));
    }

    /**
     * Update business profile and settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        // Auto-slugify input if provided (supporting English, Khmer, and Unicode characters)
        if ($request->filled('slug')) {
            $raw = trim($request->input('slug'));
            $cleanedSlug = preg_replace('/[\s_]+/u', '-', $raw);
            $cleanedSlug = preg_replace('/[^\p{L}\p{M}\p{N}\-]+/u', '', $cleanedSlug);
            $cleanedSlug = trim($cleanedSlug, '-');
            $request->merge([
                'slug' => !empty($cleanedSlug) ? mb_strtolower($cleanedSlug, 'UTF-8') : $business->slug,
            ]);
        } else {
            $request->merge(['slug' => $business->slug]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('businesses', 'slug')->ignore($business->id), 'regex:/^[\p{L}\p{M}\p{N}\-]+$/u'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_type' => ['required', 'string', 'in:' . implode(',', array_keys(config('menuhub.business_types')))],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram_handle' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'logo_url' => ['nullable', 'url', 'max:1000'],
            'logo_file' => ['nullable', 'image', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:1000'],
            'cover_file' => ['nullable', 'image', 'max:10240'],
            'default_currency' => ['required', 'string', 'in:USD,KHR'],
            'exchange_rate_khr' => ['required', 'numeric', 'min:1000'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'theme_preset' => ['nullable', 'string', 'max:50'],
            'public_url' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:30'],
            'secondary_color' => ['nullable', 'string', 'max:30'],
            'font' => ['nullable', 'string', 'max:100'],
            'layout' => ['nullable', 'string', 'in:modern,minimal,grid,list'],
            'opening_hours' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'social_links.telegram' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
        ]);

        // Upload logo / cover to Supabase Storage if files provided
        if ($request->hasFile('logo_file')) {
            $logoUrl = (new \App\Services\Storage\SupabaseStorageService())->upload($request->file('logo_file'), 'logos');
            if ($logoUrl) {
                $validated['logo_url'] = $logoUrl;
            }
        }
        unset($validated['logo_file']);

        if ($request->hasFile('cover_file')) {
            $coverUrl = (new \App\Services\Storage\SupabaseStorageService())->upload($request->file('cover_file'), 'covers');
            if ($coverUrl) {
                $validated['cover_url'] = $coverUrl;
            }
        }
        unset($validated['cover_file']);

        $existingTheme = $business->theme_settings ?? [];
        $themeSettings = array_merge($existingTheme, [
            'theme_preset' => $validated['theme_preset'] ?? ($existingTheme['theme_preset'] ?? 'orange'),
            'primary_color' => $validated['primary_color'] ?? ($existingTheme['primary_color'] ?? '#f97316'),
            'secondary_color' => $validated['secondary_color'] ?? ($existingTheme['secondary_color'] ?? '#1f2937'),
            'font' => $validated['font'] ?? ($existingTheme['font'] ?? 'Inter, Kantumruy Pro, Battambang, sans-serif'),
            'layout' => $validated['layout'] ?? ($existingTheme['layout'] ?? 'modern'),
            'public_url' => (!empty($validated['public_url']) && !Str::contains($validated['public_url'], 'kheatopup.store')) ? rtrim($validated['public_url'], '/') : null,
            'social_links' => $validated['social_links'] ?? ($existingTheme['social_links'] ?? []),
        ]);

        if (!empty($validated['social_links']['telegram'])) {
            $validated['telegram_handle'] = $validated['social_links']['telegram'];
        }

        unset(
            $validated['theme_preset'],
            $validated['primary_color'],
            $validated['secondary_color'],
            $validated['font'],
            $validated['layout'],
            $validated['public_url'],
            $validated['social_links']
        );

        $validated['theme_settings'] = $themeSettings;
        $validated['tax_percent'] = $validated['tax_percent'] ?? 0.00;

        $business->update($validated);

        return back()->with('success', 'Business profile & settings updated successfully.');
    }
}
