<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\CustomerReview;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display customer reviews & ratings management.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        $status = $request->query('status');

        $query = CustomerReview::with(['product', 'order'])
            ->where('business_id', $business->id);

        if ($status) {
            $query->where('status', $status);
        }

        $reviews = $query->latest()->paginate(15);

        $stats = [
            'total_reviews' => CustomerReview::where('business_id', $business->id)->count(),
            'average_rating' => round((float) CustomerReview::where('business_id', $business->id)->avg('rating'), 1),
            'five_star_count' => CustomerReview::where('business_id', $business->id)->where('rating', 5)->count(),
            'pending_responses' => CustomerReview::where('business_id', $business->id)->whereNull('merchant_response')->count(),
        ];

        return view('business.reviews.index', compact('business', 'reviews', 'stats', 'status'));
    }

    /**
     * Post a merchant response to a review.
     */
    public function respond(Request $request, CustomerReview $review): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($review->business_id !== $business->id, 403);

        $validated = $request->validate([
            'merchant_response' => 'required|string|max:1000',
        ]);

        $review->update([
            'merchant_response' => $validated['merchant_response'],
            'responded_at' => now(),
        ]);

        return back()->with('status', 'Response published successfully!');
    }

    /**
     * Update review publication status (published, hidden).
     */
    public function updateStatus(Request $request, CustomerReview $review): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($review->business_id !== $business->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:published,pending,hidden',
        ]);

        $review->update(['status' => $validated['status']]);

        return back()->with('status', "Review status updated to {$validated['status']}.");
    }
}
