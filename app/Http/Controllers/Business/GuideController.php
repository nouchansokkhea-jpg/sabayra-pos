<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Business\BusinessFeatureService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;

class GuideController extends Controller
{
    /**
     * Interactive Order Lifecycle Flow Guide tailored to this business type.
     */
    public function orderFlow(): View
    {
        $business = TenantContext::get();
        $publicMenuUrl = \App\Services\Qr\QrCodeService::getMenuUrl($business);
        $terminology = [
            'catalog' => BusinessFeatureService::getTerminology($business, 'catalog', 'Catalog Items'),
            'orders' => BusinessFeatureService::getTerminology($business, 'orders', 'Customer Orders'),
            'operations' => BusinessFeatureService::getTerminology($business, 'operations', 'Store Operations'),
        ];

        return view('business.guides.order_flow', compact('business', 'terminology', 'publicMenuUrl'));
    }

    /**
     * Guided Tutorials for Product, Menu, POS, and Inventory.
     */
    public function tutorials(): View
    {
        $business = TenantContext::get();
        return view('business.guides.tutorials', compact('business'));
    }
}

