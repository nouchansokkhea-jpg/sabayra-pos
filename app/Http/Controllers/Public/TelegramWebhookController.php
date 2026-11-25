<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming Telegram Bot API webhook updates securely.
     */
    public function handle(Request $request, TelegramConnectionService $connectionService): JsonResponse
    {
        $configuredSecret = config('services.telegram.webhook_secret');

        if (is_string($configuredSecret) && $configuredSecret !== '') {
            $incomingSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (!hash_equals($configuredSecret, $incomingSecret)) {
                return response()->json(['ok' => false, 'error' => 'Unauthorized webhook request.'], 403);
            }
        } elseif (app()->isProduction()) {
            return response()->json(['ok' => false, 'error' => 'Webhook secret is not configured.'], 403);
        }

        $result = $connectionService->verifyAndLinkFromWebhook($request->all());

        return response()->json([
            'ok' => true,
            'status' => $result['status'] ?? 'processed',
        ]);
    }
}
