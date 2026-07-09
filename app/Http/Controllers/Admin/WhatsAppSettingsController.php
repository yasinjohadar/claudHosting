<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvolutionInstance;
use App\Services\Ai\AIModelService;
use App\Services\QueueWorkerService;
use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyAiGenerator;
use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyHumanizer;
use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppSettingsController extends Controller
{
    public function __construct(
        private WhatsAppSettingsService $settingsService,
        private AIModelService $aiModelService,
        private QueueWorkerService $queueWorkerService,
        private WhatsAppAutoReplyAiGenerator $autoReplyAiGenerator,
        private WhatsAppAutoReplyHumanizer $autoReplyHumanizer,
        private WhatsAppAutoReplyService $autoReplyService,
    ) {}

    /**
     * Display settings page
     */
    public function index()
    {
        $this->settingsService->initializeDefaults();
        $settings = $this->settingsService->getSettings();
        $aiModels = $this->aiModelService->getAvailableModels('chat');
        $queueWorkerStatus = $this->queueWorkerService->status();
        $evolutionInstances = EvolutionInstance::connected()->orderBy('instance_name')->get(['instance_name', 'phone_number', 'profile_name']);

        return view('admin.pages.whatsapp-settings.index', compact('settings', 'aiModels', 'queueWorkerStatus', 'evolutionInstances'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_enabled' => 'nullable',
            'auto_reply' => 'nullable',
            'auto_reply_message' => 'nullable|string|max:500',
            'auto_reply_use_ai' => 'nullable',
            'auto_reply_ai_model_id' => 'nullable|integer|exists:ai_models,id',
            'auto_reply_ai_system_prompt' => 'nullable|string|max:4000',
            'auto_reply_evolution_instance' => 'nullable|string|max:150',
            'auto_reply_faq_context' => 'nullable|string|max:8000',
            'auto_reply_initial_delay_min' => 'nullable|integer|min:0|max:30',
            'auto_reply_initial_delay_max' => 'nullable|integer|min:0|max:60',
            'auto_reply_typing_duration' => 'nullable|integer|min:1|max:15',
            'auto_reply_max_chunks' => 'nullable|integer|min:1|max:5',
            'auto_reply_chunk_max_chars' => 'nullable|integer|min:100|max:1000',
            'auto_reply_contact_cooldown' => 'nullable|integer|min:10|max:600',
            'auto_reply_debounce_seconds' => 'nullable|integer|min:1|max:60',
            'auto_reply_test_phone' => 'nullable|string|max:30',
            'timeout' => 'nullable|integer|min:1|max:300',
            'delay_between_messages' => 'nullable|integer|min:1|max:60',
            'delay_between_broadcasts' => 'nullable|integer|min:1|max:60',
            'max_messages_per_minute' => 'nullable|integer|min:1|max:100',
            'random_delay_enabled' => 'nullable',
            'min_delay' => 'nullable|integer|min:1|max:10',
            'max_delay' => 'nullable|integer|min:1|max:10',
        ], [
            'timeout.integer' => 'المهلة الزمنية يجب أن تكون رقماً',
            'timeout.min' => 'المهلة الزمنية يجب أن تكون على الأقل ثانية واحدة',
            'timeout.max' => 'المهلة الزمنية يجب أن تكون أقل من 300 ثانية',
        ]);

        try {
            $validated['whatsapp_provider'] = 'evolution';
            $validated['whatsapp_enabled'] = $request->has('whatsapp_enabled') ? '1' : '0';
            $validated['auto_reply'] = $request->has('auto_reply') ? '1' : '0';
            $validated['auto_reply_use_ai'] = $request->has('auto_reply_use_ai') ? '1' : '0';
            $validated['random_delay_enabled'] = $request->has('random_delay_enabled') ? '1' : '0';

            if (empty($validated['auto_reply_ai_model_id'])) {
                $validated['auto_reply_ai_model_id'] = '';
            }
            $validated['auto_reply_ai_system_prompt'] = $validated['auto_reply_ai_system_prompt'] ?? '';
            $validated['auto_reply_faq_context'] = $validated['auto_reply_faq_context'] ?? '';
            $validated['auto_reply_evolution_instance'] = $validated['auto_reply_evolution_instance'] ?? '';
            $validated['auto_reply_test_phone'] = $validated['auto_reply_test_phone'] ?? '';

            if (isset($validated['auto_reply_initial_delay_max'], $validated['auto_reply_initial_delay_min'])
                && (int) $validated['auto_reply_initial_delay_max'] < (int) $validated['auto_reply_initial_delay_min']) {
                $validated['auto_reply_initial_delay_max'] = $validated['auto_reply_initial_delay_min'];
            }

            $this->settingsService->updateSettings($validated);

            return redirect()->route('admin.whatsapp-settings.index')
                ->with('success', 'تم حفظ الإعدادات بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating WhatsApp settings: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حفظ الإعدادات: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Preview AI auto-reply without sending WhatsApp message.
     */
    public function autoReplyPreview(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
        ]);

        $settings = $this->settingsService->getAutoReplySettings();
        $result = $this->autoReplyAiGenerator->preview(
            $settings,
            $validated['question'],
            $this->autoReplyHumanizer,
        );

        return response()->json([
            'success' => true,
            'reply' => $result['reply'],
            'chunks' => $result['chunks'],
        ]);
    }

    /**
     * Send full auto-reply pipeline to a test phone number.
     */
    public function autoReplyTestSend(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'test_phone' => 'nullable|string|max:30',
        ]);

        $settings = $this->settingsService->getAutoReplySettings();
        $phone = trim($validated['test_phone'] ?? $settings['auto_reply_test_phone'] ?? '');

        if ($phone === '') {
            return response()->json([
                'success' => false,
                'message' => 'أدخل رقم اختبار في الحقل أو احفظه في الإعدادات.',
            ], 422);
        }

        try {
            $this->autoReplyService->testSend($settings, $phone, $validated['question']);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرد التجريبي بنجاح.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Auto-reply test send failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test connection to WhatsApp API
     */
    public function testConnection(Request $request)
    {
        try {
            $settings = $this->settingsService->getSettings();
            $config = [
                'base_url' => $settings['evolution_base_url'] ?? '',
                'api_key' => $settings['evolution_api_key'] ?? '',
                'instance_name' => $settings['evolution_instance_name'] ?? '',
            ];

            $providerInstance = WhatsAppProviderFactory::create('evolution', $config);
            $result = $providerInstance->testConnection();

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Error testing WhatsApp connection: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Queue worker: get status (for AJAX or initial page load).
     */
    public function queueWorkerStatus()
    {
        $status = $this->queueWorkerService->status();

        return response()->json($status);
    }

    /**
     * Queue worker: start.
     */
    public function queueWorkerStart()
    {
        $result = $this->queueWorkerService->start();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Queue worker: stop.
     */
    public function queueWorkerStop()
    {
        $result = $this->queueWorkerService->stop();

        return response()->json($result);
    }

    /**
     * Parse headers from JSON string or array
     */
    protected function parseHeaders($headers): array
    {
        if (is_array($headers)) {
            return $headers;
        }

        if (is_string($headers)) {
            try {
                $decoded = json_decode($headers, true);

                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e) {
                return [];
            }
        }

        return [];
    }
}
