<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\Business;
use App\Models\User;
use App\Services\AI\AiAccessService;
use App\Services\AI\AIManager;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIUsageService;
use App\Services\AI\BusinessAssistantService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly BusinessAssistantService $assistantService,
        private readonly AIManager $aiManager,
        private readonly AIUsageService $usageService,
        private readonly AiAccessService $accessService,
    ) {}

    /**
     * Render the SABAYRA AI Business Assistant workspace with persisted chat threads.
     */
    public function index(Request $request): View
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);

        $conversations = AiConversation::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $activeConversation = null;
        $requestedId = (string) $request->query('conversation', '');

        if ($requestedId === 'new') {
            $activeConversation = null;
        } elseif ($requestedId !== '') {
            $activeConversation = $conversations->firstWhere('id', $requestedId);
        } elseif ($conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        $messages = $activeConversation
            ? $activeConversation->messages()->get()
            : collect();

        return view('business.ai.assistant', compact(
            'business',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas',
            'featureSettings',
            'conversations',
            'activeConversation',
            'messages'
        ));
    }

    /**
     * Handle a conversational AI Business Assistant prompt and return structured JSON output.
     */
    public function ask(Request $request): JsonResponse
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'conversation_id' => ['nullable', 'uuid'],
            'request_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $result = $this->assistantService->reply(
                $business,
                $user,
                (string) $validated['message'],
                $validated['conversation_id'] ?? null,
                $validated['request_uuid'] ?? null
            );

            return response()->json([
                'ok' => true,
                'conversation' => [
                    'id' => $result['conversation']->id,
                    'title' => $result['conversation']->title,
                    'url' => route('business.ai.assistant', ['conversation' => $result['conversation']->id]),
                ],
                'user_message' => [
                    'id' => $result['user_message']->id,
                    'role' => 'user',
                    'content' => $result['user_message']->content,
                    'language_detected' => $result['user_message']->language_detected,
                    'created_at' => $result['user_message']->created_at?->toIso8601String(),
                ],
                'assistant_message' => [
                    'id' => $result['assistant_message']->id,
                    'role' => 'assistant',
                    'content' => $result['assistant_message']->content,
                    'provider' => $result['assistant_message']->provider,
                    'model' => $result['assistant_message']->model,
                    'data_sources' => $result['data_sources'],
                    'created_at' => $result['assistant_message']->created_at?->toIso8601String(),
                ],
                'monthly_summary' => $this->usageService->getMonthlySummary($business),
            ]);
        } catch (AIProviderException $exception) {
            return response()->json([
                'ok' => false,
                'error_code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'monthly_summary' => $this->usageService->getMonthlySummary($business),
            ], $exception->httpStatus);
        }
    }

    /**
     * Delete a conversation belonging strictly to the authenticated user and business tenant.
     */
    public function destroy(AiConversation $conversation): RedirectResponse
    {
        [$business, $user] = $this->resolveAndAuthorize();

        if ($conversation->business_id !== $business->id || $conversation->user_id !== $user->id) {
            abort(403, 'Unauthorized conversation access.');
        }

        $conversation->delete();

        return redirect()->route('business.ai.assistant')->with('success', 'Conversation deleted.');
    }

    /**
     * Resolve active tenant business and verify assistant permission.
     *
     * @return array{0: Business, 1: User}
     */
    private function resolveAndAuthorize(): array
    {
        /** @var Business|null $business */
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user) {
            abort(403, 'Active business workspace required.');
        }

        $this->accessService->authorizeArea($user, $business, 'assistant');

        return [$business, $user];
    }
}
