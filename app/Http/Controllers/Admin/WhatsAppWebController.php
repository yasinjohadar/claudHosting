<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppWebSession;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebController extends Controller
{
    public function __construct(
        private WhatsAppSettingsService $settingsService
    ) {}

    /**
     * Show connect page with QR Code
     */
    public function connect()
    {
        // Get active session - check connected first, then connecting
        $session = WhatsAppWebSession::where('status', 'connected')
            ->latest()
            ->first();
            
        // If no connected session, check for connecting session
        if (!$session) {
            $session = WhatsAppWebSession::where('status', 'connecting')
                ->latest()
                ->first();
        }

        $settings = $this->settingsService->getSettings();
        $nodejsUrl = $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000';
        $apiToken = $settings['whatsapp_web_api_token'] ?? '';

        return view('admin.pages.whatsapp-web.connect', compact('session', 'nodejsUrl', 'apiToken'));
    }

    /**
     * Start connection (create session)
     */
    public function startConnection(Request $request)
    {
        try {
            $settings = $this->settingsService->getSettings();
            $nodejsUrl = $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000';
            $apiToken = $settings['whatsapp_web_api_token'] ?? '';

            // Generate session ID
            $sessionId = 'session_' . Str::random(32);

            // Call Node.js service to start connection
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiToken,
                    ])
                    ->post("{$nodejsUrl}/api/whatsapp/connect", [
                        'session_id' => $sessionId,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Check if session already exists
                    $session = WhatsAppWebSession::where('session_id', $sessionId)->first();
                    
                    if (!$session) {
                        // Create new session record
                        $session = WhatsAppWebSession::create([
                            'session_id' => $sessionId,
                            'status' => 'connecting',
                            'qr_code' => $data['qr_code'] ?? null,
                        ]);
                    } else {
                        // Update existing session
                        $session->update([
                            'status' => 'connecting',
                            'qr_code' => $data['qr_code'] ?? null,
                            'error_message' => null,
                        ]);
                    }
                    
                    // If already connected in response, update immediately
                    if (isset($data['connected']) && $data['connected']) {
                        $session->markAsConnected(
                            $data['phone_number'] ?? '',
                            $data['name'] ?? ''
                        );
                    }

                    return response()->json([
                        'success' => true,
                        'session_id' => $sessionId,
                        'qr_code' => $data['qr_code'] ?? null,
                        'connected' => $data['connected'] ?? false,
                        'message' => isset($data['connected']) && $data['connected'] 
                            ? 'متصل بنجاح' 
                            : 'تم بدء عملية الربط',
                    ]);
                }

                $errorMessage = 'فشل الاتصال بخدمة WhatsApp Web';
                if ($response->status() === 0 || $response->status() === 404) {
                    $errorMessage = 'خدمة Node.js غير متاحة. تأكد من أن الخدمة تعمل على: ' . $nodejsUrl;
                } elseif ($response->status() === 500) {
                    $errorData = $response->json();
                    $errorMessage = $errorData['error'] ?? $errorData['message'] ?? $errorMessage;
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن الاتصال بخدمة Node.js. تأكد من أن الخدمة تعمل على: ' . $nodejsUrl . ' - الخطأ: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WhatsApp Web connection error - Node.js service unavailable', [
                'error' => $e->getMessage(),
                'nodejs_url' => $nodejsUrl ?? 'not set',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الاتصال بخدمة Node.js على: ' . ($nodejsUrl ?? 'غير محدد') . '. تأكد من أن الخدمة تعمل.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp Web connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR Code for session
     */
    public function getQrCode($sessionId)
    {
        try {
            $session = WhatsAppWebSession::where('session_id', $sessionId)->firstOrFail();
            
            $settings = $this->settingsService->getSettings();
            $nodejsUrl = $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000';
            $apiToken = $settings['whatsapp_web_api_token'] ?? '';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                ])
                ->get("{$nodejsUrl}/api/whatsapp/qr/{$sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                
                // Update session with new QR code
                $session->updateQrCode($data['qr_code'] ?? '');

                return response()->json([
                    'success' => true,
                    'qr_code' => $data['qr_code'],
                    'status' => $data['status'] ?? 'connecting',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل الحصول على QR Code',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get connection status
     */
    public function getStatus($sessionId)
    {
        try {
            $session = WhatsAppWebSession::where('session_id', $sessionId)->firstOrFail();
            
            $settings = $this->settingsService->getSettings();
            $nodejsUrl = $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000';
            $apiToken = $settings['whatsapp_web_api_token'] ?? '';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                ])
                ->get("{$nodejsUrl}/api/whatsapp/status/{$sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                
                // Update session status based on response
                if (isset($data['connected']) && $data['connected']) {
                    // Only update if not already connected or info changed
                    if ($session->status !== 'connected' || 
                        $session->phone_number !== ($data['phone_number'] ?? '') ||
                        $session->name !== ($data['name'] ?? '')) {
                        $session->markAsConnected(
                            $data['phone_number'] ?? '',
                            $data['name'] ?? ''
                        );
                    }
                    // Refresh to get latest data
                    $session->refresh();
                } elseif (isset($data['connected']) && !$data['connected']) {
                    // Only update if status changed
                    if ($session->status === 'connected') {
                        $session->markAsDisconnected($data['error'] ?? null);
                    }
                }

                return response()->json([
                    'success' => true,
                    'connected' => $data['connected'] ?? false,
                    'status' => $session->status,
                    'phone_number' => $session->phone_number,
                    'name' => $session->name,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل الحصول على حالة الاتصال',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect session
     */
    public function disconnect($sessionId)
    {
        try {
            $session = WhatsAppWebSession::where('session_id', $sessionId)->firstOrFail();
            
            $settings = $this->settingsService->getSettings();
            $nodejsUrl = $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000';
            $apiToken = $settings['whatsapp_web_api_token'] ?? '';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                ])
                ->post("{$nodejsUrl}/api/whatsapp/disconnect/{$sessionId}");

            // Mark session as disconnected regardless of response
            $session->markAsDisconnected();

            return response()->json([
                'success' => true,
                'message' => 'تم قطع الاتصال بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Web disconnect error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }
}
