<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Verify webhook
     */
    public function verify(Request $request): Response
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $challenge) {
            $verifiedToken = $this->whatsappService->verifyWebhook($token, $challenge);
            
            if ($verifiedToken) {
                return response($verifiedToken, Response::HTTP_OK);
            }
        }

        return response('Verification failed', Response::HTTP_FORBIDDEN);
    }

    /**
     * Handle incoming webhook
     */
    public function handle(Request $request): Response
    {
        try {
            $payload = $request->json()->all();
            
            \Log::info('WhatsApp webhook received', ['payload' => $payload]);
            
            $this->whatsappService->processWebhook($payload);
            
            return response('Webhook processed', Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Failed to process webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->json()->all(),
            ]);
            
            return response('Webhook processing failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
