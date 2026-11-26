<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Payment\AbaPayWayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private AbaPayWayService $abaPayWayService,
    ) {
    }

    /**
     * Receive authenticated ABA PayWay server-to-server webhook notifications.
     */
    public function abaPayWay(Request $request, string $reference): JsonResponse
    {
        $result = $this->abaPayWayService->handleWebhookCallback($request, $reference);

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
        ], $result['status_code']);
    }
}
