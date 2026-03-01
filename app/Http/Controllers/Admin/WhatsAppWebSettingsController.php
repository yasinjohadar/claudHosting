<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppWebSession;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebSettingsController extends Controller
{
    public function __construct(
        private WhatsAppSettingsService $settingsService
    ) {}

    /**
     * Display WhatsApp Web settings page
     */
    public function index()
    {
        $this->settingsService->initializeDefaults();
        $settings = $this->settingsService->getSettings();
        
        // Get active session - prioritize connected status
        $session = WhatsAppWebSession::where('status', 'connected')
            ->latest()
            ->first();
            
        // If no connected session, check for connecting session
        if (!$session) {
            $session = WhatsAppWebSession::where('status', 'connecting')
                ->latest()
                ->first();
        }
        
        // If session exists, refresh status from Node.js service
        if ($session && $session->status === 'connected') {
            try {
                $settings = $this->settingsService->getSettings();
                $nodejsUrl = $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000';
                $apiToken = $settings['whatsapp_web_api_token'] ?? '';
                
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiToken,
                    ])
                    ->get("{$nodejsUrl}/api/whatsapp/status/{$session->session_id}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['connected']) && !$data['connected']) {
                        // Session disconnected, update database
                        $session->markAsDisconnected();
                        $session = null;
                    } elseif (isset($data['connected']) && $data['connected']) {
                        // Update session info if changed
                        if (isset($data['phone_number']) && $data['phone_number'] !== $session->phone_number) {
                            $session->markAsConnected(
                                $data['phone_number'] ?? '',
                                $data['name'] ?? ''
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors, just use database status
            }
        }

        return view('admin.pages.whatsapp-web-settings.index', compact('settings', 'session'));
    }

    /**
     * Update WhatsApp Web settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_web_service_url' => 'required|string|url|max:500',
            'whatsapp_web_api_token' => 'nullable|string|max:500',
            'delay_between_messages' => 'nullable|integer|min:1|max:60',
            'delay_between_broadcasts' => 'nullable|integer|min:1|max:60',
            'max_messages_per_minute' => 'nullable|integer|min:1|max:100',
            'random_delay_enabled' => 'nullable',
            'min_delay' => 'nullable|integer|min:1|max:10',
            'max_delay' => 'nullable|integer|min:1|max:10',
        ], [
            'whatsapp_web_service_url.required' => 'رابط Node.js service مطلوب',
            'whatsapp_web_service_url.url' => 'رابط Node.js service غير صالح',
        ]);

        try {
            // Handle checkbox
            $validated['random_delay_enabled'] = $request->has('random_delay_enabled') ? '1' : '0';

            // If api_token is empty, keep existing value
            if (empty($validated['whatsapp_web_api_token'])) {
                $existingSettings = $this->settingsService->getSettings();
                $validated['whatsapp_web_api_token'] = $existingSettings['whatsapp_web_api_token'] ?? '';
            }

            // Update settings
            $this->settingsService->updateSettings($validated);

            return redirect()->route('admin.whatsapp-web-settings.index')
                           ->with('success', 'تم حفظ الإعدادات بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating WhatsApp Web settings: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Test connection to Node.js service
     */
    public function testConnection(Request $request)
    {
        try {
            $settings = $this->settingsService->getSettings();
            $nodejsUrl = $request->input('whatsapp_web_service_url', $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000');
            $apiToken = $request->input('whatsapp_web_api_token', $settings['whatsapp_web_api_token'] ?? '');

            // First check if Node.js service is reachable
            try {
                $healthCheck = \Illuminate\Support\Facades\Http::timeout(5)->get("{$nodejsUrl}/health");
                if (!$healthCheck->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Node.js service غير متاح على: ' . $nodejsUrl,
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن الوصول إلى Node.js service على: ' . $nodejsUrl . '. تأكد من أن الخدمة تعمل.',
                ], 500);
            }

            $config = [
                'nodejs_service_url' => $nodejsUrl,
                'api_token' => $apiToken,
            ];

            // Create provider and test connection
            $providerInstance = \App\Services\WhatsApp\WhatsAppProviderFactory::create('whatsapp_web', $config);
            $result = $providerInstance->testConnection();

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Error testing WhatsApp Web connection: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }
}
