<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvolutionWebhookAdminController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService,
        private WhatsAppSettingsService $settingsService
    ) {}

    public function index(): View
    {
        $instance = $this->evolutionService->activeInstanceName();
        $webhook = null;
        $error = null;

        try {
            if ($instance !== '') {
                $webhook = $this->evolutionService->client()->getWebhook($instance);
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $settings = $this->evolutionService->getSettings();
        $webhookBaseUrl = $settings['evolution_webhook_base_url'] ?? '';
        $appUrl = rtrim((string) config('app.url'), '/');

        return view('admin.pages.evolution-api.webhook.index', [
            'instance' => $instance,
            'webhook' => $webhook,
            'webhookUrl' => $this->evolutionService->webhookUrl($instance),
            'webhookBaseUrl' => $webhookBaseUrl,
            'appUrl' => $appUrl,
            'events' => $this->evolutionService->defaultWebhookEvents(),
            'error' => $error,
            'webhookEventsCount' => \App\Models\WhatsAppWebhookEvent::count(),
            'isLocalWebhookUrl' => $this->evolutionService->isLocalWebhookBaseUrl(),
            'usesCustomWebhookBaseUrl' => $webhookBaseUrl !== '',
        ]);
    }

    public function saveUrl(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evolution_webhook_base_url' => ['nullable', 'string', 'max:500'],
        ]);

        $url = rtrim(trim((string) ($validated['evolution_webhook_base_url'] ?? '')), '/');
        if ($url !== '' && ! filter_var($url, FILTER_VALIDATE_URL)) {
            return back()
                ->withInput()
                ->with('error', 'رابط المنصة غير صالح. استخدم صيغة مثل https://lms.example.com');
        }

        $this->settingsService->updateSettings([
            'evolution_webhook_base_url' => $url,
        ]);

        $message = $url === ''
            ? 'تمت إعادة استخدام APP_URL الافتراضي لرابط Webhook.'
            : 'تم حفظ رابط المنصة العام: '.$url;

        return back()->with('success', $message);
    }

    public function activate(Request $request): RedirectResponse
    {
        $instance = $this->evolutionService->activeInstanceName();
        abort_if($instance === '', 422, 'حدّد Instance في الإعدادات أولاً.');

        $validated = $request->validate([
            'evolution_webhook_base_url' => ['nullable', 'string', 'max:500'],
        ]);

        $url = rtrim(trim((string) ($validated['evolution_webhook_base_url'] ?? '')), '/');
        if ($url !== '' && ! filter_var($url, FILTER_VALIDATE_URL)) {
            return back()
                ->withInput()
                ->with('error', 'رابط المنصة غير صالح. استخدم صيغة مثل https://lms.example.com');
        }

        if ($request->has('evolution_webhook_base_url')) {
            $this->settingsService->updateSettings([
                'evolution_webhook_base_url' => $url,
            ]);
        }

        $webhookUrl = $this->evolutionService->webhookUrl($instance);
        $settings = $this->evolutionService->getSettings();

        try {
            $this->evolutionService->client()->setWebhook($instance, [
                'enabled' => true,
                'url' => $webhookUrl,
                'webhookByEvents' => false,
                'webhookBase64' => false,
                'events' => $this->evolutionService->defaultWebhookEvents(),
                'headers' => array_filter([
                    'apikey' => $settings['evolution_api_key'] ?? null,
                ]),
            ]);

            return back()->with('success', 'تم تفعيل Webhook بنجاح على Evolution. الرابط: '.$webhookUrl);
        } catch (\Throwable $e) {
            return back()->with('error', 'فشل تفعيل Webhook: '.$e->getMessage());
        }
    }
}
