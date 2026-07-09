<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvolutionInstance;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvolutionSettingsController extends Controller
{
    public function __construct(
        private WhatsAppSettingsService $settingsService,
        private EvolutionService $evolutionService
    ) {}

    public function index(): View
    {
        $this->settingsService->initializeDefaults();
        $settings = $this->settingsService->getSettings();

        $apiInfo = null;
        $connection = null;
        if (($settings['evolution_base_url'] ?? '') !== '' && ($settings['evolution_api_key'] ?? '') !== '') {
            try {
                $client = $this->evolutionService->client();
                $apiInfo = $client->getInformation();
                if (! empty($settings['evolution_instance_name'])) {
                    $connection = $client->getConnectionState($settings['evolution_instance_name']);
                }
            } catch (\Throwable) {
                // shown in UI via test button
            }
        }

        return view('admin.pages.evolution-api.settings.index', [
            'settings' => $settings,
            'hasApiKey' => ($settings['evolution_api_key'] ?? '') !== '',
            'apiInfo' => $apiInfo,
            'connection' => $connection,
            'webhookUrl' => $this->evolutionService->webhookUrl(),
            'syncedInstances' => EvolutionInstance::orderBy('instance_name')->get(['instance_name', 'phone_number', 'connection_status']),
            'rotationPoolCount' => EvolutionInstance::rotationPoolCount(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evolution_base_url' => ['required', 'string', 'max:500'],
            'evolution_api_key' => ['nullable', 'string', 'max:500'],
            'evolution_instance_name' => ['required', 'string', 'max:150'],
            'evolution_rotation_enabled' => ['nullable', 'boolean'],
            'evolution_webhook_secret' => ['nullable', 'string', 'max:500'],
            'whatsapp_provider' => ['nullable', 'string', 'in:evolution'],
        ]);

        $existing = $this->settingsService->getSettings();

        if (empty($validated['evolution_api_key'])) {
            $validated['evolution_api_key'] = $existing['evolution_api_key'] ?? '';
        }
        if (empty($validated['evolution_webhook_secret'])) {
            $validated['evolution_webhook_secret'] = $existing['evolution_webhook_secret'] ?? '';
        }

        $this->settingsService->updateSettings([
            'evolution_base_url' => $validated['evolution_base_url'],
            'evolution_api_key' => $validated['evolution_api_key'],
            'evolution_instance_name' => $validated['evolution_instance_name'],
            'evolution_rotation_enabled' => $request->boolean('evolution_rotation_enabled') ? '1' : '0',
            'evolution_webhook_secret' => $validated['evolution_webhook_secret'],
            'whatsapp_enabled' => '1',
            'whatsapp_provider' => $request->input('whatsapp_provider', 'evolution'),
        ]);

        $this->evolutionService->registerManualInstance([
            'instance_name' => $validated['evolution_instance_name'],
            'verify' => false,
            'set_as_default' => true,
        ]);

        try {
            $this->evolutionService->syncInstances(false);
        } catch (\Throwable) {
            // non-blocking after save
        }

        return redirect()
            ->route('admin.evolution-api.settings.index')
            ->with('success', 'تم حفظ إعدادات Evolution API. لم يُحذف أي Instance يدوي من القائمة.');
    }

    public function testConnection(Request $request): JsonResponse
    {
        $request->validate([
            'evolution_base_url' => ['nullable', 'string', 'max:500'],
            'evolution_api_key' => ['nullable', 'string', 'max:500'],
            'evolution_instance_name' => ['nullable', 'string', 'max:150'],
        ]);

        $existing = $this->settingsService->getSettings();
        $config = [
            'base_url' => $request->input('evolution_base_url', $existing['evolution_base_url'] ?? ''),
            'api_key' => $request->input('evolution_api_key') ?: ($existing['evolution_api_key'] ?? ''),
            'instance_name' => $request->input('evolution_instance_name', $existing['evolution_instance_name'] ?? ''),
        ];

        $provider = WhatsAppProviderFactory::create('evolution', $config);

        return response()->json($provider->testConnection());
    }
}
