<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\NotificationPreference;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class NotificationPreferencesController extends Controller
{
    /**
     * Save business-level notification event preferences across channels.
     */
    public function update(Request $request): RedirectResponse
    {
        $business = TenantContext::get();
        $user = Auth::user();

        if (! $business instanceof Business || ! $user || ! $user->canManageBusinessSettings($business)) {
            abort(403, 'You do not have permission to manage notification preferences.');
        }

        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'uuid',
                Rule::exists('business_branches', 'id')->where('business_id', $business->id),
            ],
            'preferences' => ['nullable', 'array'],
        ]);

        $branchId = $validated['branch_id'] ?? null;
        $submittedPreferences = $request->input('preferences', []);
        $submittedPreferences = is_array($submittedPreferences) ? $submittedPreferences : [];

        foreach (array_keys(NotificationPreference::EVENT_CATALOG) as $eventKey) {
            $input = is_array($submittedPreferences[$eventKey] ?? null) ? $submittedPreferences[$eventKey] : [];

            $existing = NotificationPreference::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereNull('user_id')
                ->where('event_key', $eventKey)
                ->first();

            $attributes = [
                'branch_id' => $branchId,
                'telegram_enabled' => filter_var($input['telegram_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'email_enabled' => filter_var($input['email_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'in_app_enabled' => filter_var($input['in_app_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'browser_enabled' => filter_var($input['browser_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            if ($existing) {
                $existing->update($attributes);
            } else {
                NotificationPreference::create(array_merge($attributes, [
                    'business_id' => $business->id,
                    'user_id' => null,
                    'event_key' => $eventKey,
                ]));
            }
        }

        return redirect()
            ->route('business.settings.index', ['section' => 'notifications'])
            ->with('status', 'Notification preferences saved.');
    }

    /**
     * Mark a single in-app notification belonging to the current user as read.
     */
    public function markAsRead(Request $request, string $notificationId): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $business = TenantContext::get();

        if (! $user || ! Schema::hasTable('notifications')) {
            abort(404);
        }

        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            abort(404);
        }

        if ($business && ($notification->data['business_id'] ?? null) !== $business->id) {
            abort(403, 'Notification does not belong to the active business context.');
        }

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all unread in-app notifications in the current business context as read.
     */
    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $business = TenantContext::get();

        if ($user && Schema::hasTable('notifications')) {
            $unread = $user->unreadNotifications()->get();

            foreach ($unread as $notification) {
                if (! $business || ($notification->data['business_id'] ?? null) === $business->id) {
                    $notification->markAsRead();
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }
}
