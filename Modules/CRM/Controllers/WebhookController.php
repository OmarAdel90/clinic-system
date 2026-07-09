<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(protected WebhookService $service) {}

    public function verify(Request $request): JsonResponse|string
    {
        $mode = $request->query('hub.mode', $request->query('hub_mode'));
        $token = $request->query('hub.verify_token', $request->query('hub_verify_token'));
        $challenge = $request->query('hub.challenge', $request->query('hub_challenge'));

        if ($mode === 'subscribe' && $this->service->verifyToken($token)) {
            return $challenge;
        }

        return response()->json(['message' => 'Verification failed.'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256', '');
        $rawBody = $request->getContent();

        if (!$this->service->verifySignature($rawBody, $signature)) {
            Log::warning('Webhook signature verification failed.', ['signature' => $signature]);
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = $request->all();

        $this->service->process($payload, $request->headers->all());

        return response()->json(['status' => 'ok'], 200);
    }
}
