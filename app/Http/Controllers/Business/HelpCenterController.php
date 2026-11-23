<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpCenterController extends Controller
{
    /**
     * Display Help Center, FAQs, and ticket submission.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $tickets = SupportTicket::where('user_id', Auth::id())->latest()->get();

        return view('business.help.index', compact('business', 'tickets'));
    }

    /**
     * Submit an inquiry or support request.
     */
    public function storeTicket(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:billing,technical,general,feature_request',
            'message' => 'required|string|max:3000',
        ]);

        SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'status' => 'open',
            'priority' => 'medium',
        ]);

        return back()->with('status', 'Support ticket submitted! Our Phnom Penh operations team will respond promptly.');
    }
}
