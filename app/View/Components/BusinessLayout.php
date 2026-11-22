<?php

namespace App\View\Components;

use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class BusinessLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $heading = null
    ) {
    }

    public function render(): View
    {
        $currentBusiness = TenantContext::get();
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $userBusinesses = $user
            ? $user->ownedBusinesses()->get()->merge($user->businesses()->get())->unique('id')
            : collect();

        return view('layouts.business', compact('currentBusiness', 'userBusinesses'));
    }
}
