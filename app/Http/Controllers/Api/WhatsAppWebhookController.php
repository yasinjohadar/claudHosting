<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhookEventJob;
use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsApp\SignatureVerifier;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppSettingsService $settingsService;

    public function __construct(WhatsAppSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Verify webhook (GET request from Meta)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $settings = $this->settingsService->getSettings();
        $verifyToken = $settings['verify_token'] ?? '';

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::channel('whatsapp')->info('Webhook verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('whatsapp')->warning('Webhook verification failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Verification failed', 403);
    }

    /**
     * Handle webhook (POST request from Meta)
     */
    public function handle(Request $request)
    {
        // Get raw body for signature verification
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');

        // Get settings from database
        $settings = $this->settingsService->getSettings();
        $strictSignature = $settings['strict_signature'] ?? true;
        
        // Verify signature if strict mode is enabled
        if ($strictSignature) {
            $appSecret = $settings['app_secret'] ?? '';
            if (!empty($appSecret) && !SignatureVerifier::verifyFromRequest($signature, $rawBody, $appSecret, $strictSignature)) {
                Log::channel('whatsapp')->warning('Invalid webhook signature', [
                    'signature' => substr($signature ?? '', 0, 20) . '...',
                ]);
                return response('Invalid signature', 401);
            }
        }

        try {
            $payload = $request->json()->all();

            // Generate unique event ID for idempotency
            $eventId = $this->generateEventId($payload);

            // Check if event already processed
            $existingEvent = WhatsAppWebhookEvent::where('event_id', $eventId)->first();
            if ($existingEvent && $existingEvent->isProcessed()) {
                Log::channel('whatsapp')->info('Webhook event already processed', [
                    'event_id' => $eventId,
                ]);
                return response('OK', 200);
            }

            // Store event
            $webhookEvent = WhatsAppWebhookEvent::firstOrCreate(
                ['event_id' => $eventId],
                ['payload' => $payload]
            );

            // Dispatch job to process event
            ProcessWhatsAppWebhookEventJob::dispatch($webhookEvent);

            Log::channel('whatsapp')->info('Webhook event received and queued', [
                'event_id' => $eventId,
                'webhook_event_id' => $webhookEvent->id,
            ]);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error handling webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Generate unique event ID from payload for idempotency
     */
    protected function generateEventId(array $payload): string
    {
        // Use entry ID + change field + message/status ID + timestamp
        $entry = $payload['entry'][0] ?? [];
        $change = $entry['changes'][0] ?? [];
        $value = $change['value'] ?? [];

        $parts = [
            $entry['id'] ?? 'unknown',
            $change['field'] ?? 'unknown',
        ];

        // Add message ID or status ID if available
        if (isset($value['messages'][0]['id'])) {
            $parts[] = $value['messages'][0]['id'];
            $parts[] = $value['messages'][0]['timestamp'] ?? time();
        } elseif (isset($value['statuses'][0]['id'])) {
            $parts[] = $value['statuses'][0]['id'];
            $parts[] = $value['statuses'][0]['timestamp'] ?? time();
        } else {
            $parts[] = md5(json_encode($payload));
            $parts[] = time();
        }

        return hash('sha256', implode('|', $parts));
    }
}
