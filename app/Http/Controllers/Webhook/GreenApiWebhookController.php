<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WhatsappWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GreenApiWebhookController extends Controller
{
    public function __construct(private WhatsappWebhookService $whatsappWebhookService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $configuredSecret = (string) config('services.green_api.webhook_secret');
        $providedSecret = (string) ($request->query('secret') ?? $request->header('X-GreenApi-Secret') ?? '');

        if ($configuredSecret !== '' && !hash_equals($configuredSecret, $providedSecret)) {
            abort(403);
        }

        $this->whatsappWebhookService->handleIncoming($request->all());

        return response()->json(['success' => true]);
    }
}
