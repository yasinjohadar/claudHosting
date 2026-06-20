<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\VpsServer;
use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\VpsProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InfrastructureSettingsController extends Controller
{
    public function __construct(
        protected InfrastructureSettingsService $settings,
        protected VpsProviderRegistry $registry
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $providers = VpsServer::PROVIDERS;
        $activeProvider = $request->string('provider')->toString();
        if (! array_key_exists($activeProvider, $providers)) {
            $activeProvider = 'contabo';
        }

        $configured = [];
        foreach (array_keys($providers) as $key) {
            $configured[$key] = $this->settings->isProviderConfigured($key);
        }

        return view('admin.infrastructure.settings.index', [
            'form' => $this->settings->getFormSettings(),
            'providers' => $providers,
            'activeProvider' => $activeProvider,
            'configured' => $configured,
            'ovhEndpoints' => config('infrastructure.ovh.endpoints', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $provider = $request->validate([
            'provider' => ['required', Rule::in(array_keys(VpsServer::PROVIDERS))],
        ])['provider'];

        $rules = match ($provider) {
            'contabo' => [
                'contabo_client_id' => 'nullable|string|max:255',
                'contabo_client_secret' => 'nullable|string|max:500',
                'contabo_api_user' => 'nullable|string|max:255',
                'contabo_api_password' => 'nullable|string|max:500',
            ],
            'hetzner' => ['hetzner_api_token' => 'nullable|string|max:500'],
            'digitalocean' => ['digitalocean_api_token' => 'nullable|string|max:500'],
            'ovh' => [
                'ovh_application_key' => 'nullable|string|max:255',
                'ovh_application_secret' => 'nullable|string|max:500',
                'ovh_consumer_key' => 'nullable|string|max:500',
                'ovh_endpoint' => 'nullable|string|max:32',
            ],
            'netcup' => [
                'netcup_client_id' => 'nullable|string|max:255',
                'netcup_client_secret' => 'nullable|string|max:500',
            ],
            default => [],
        };

        $validated = $request->validate($rules);
        $this->settings->save($validated);

        $label = VpsServer::PROVIDERS[$provider] ?? $provider;

        return redirect()
            ->route('admin.infrastructure.settings.index', ['provider' => $provider])
            ->with('success', 'تم حفظ إعدادات '.$label);
    }

    public function testConnection(Request $request): RedirectResponse
    {
        $provider = $request->validate([
            'provider' => ['required', Rule::in(array_keys(VpsServer::PROVIDERS))],
        ])['provider'];

        $rules = match ($provider) {
            'contabo' => [
                'contabo_client_id' => 'nullable|string|max:255',
                'contabo_client_secret' => 'nullable|string|max:500',
                'contabo_api_user' => 'nullable|string|max:255',
                'contabo_api_password' => 'nullable|string|max:500',
            ],
            'hetzner' => ['hetzner_api_token' => 'nullable|string|max:500'],
            'digitalocean' => ['digitalocean_api_token' => 'nullable|string|max:500'],
            'ovh' => [
                'ovh_application_key' => 'nullable|string|max:255',
                'ovh_application_secret' => 'nullable|string|max:500',
                'ovh_consumer_key' => 'nullable|string|max:500',
                'ovh_endpoint' => 'nullable|string|max:32',
            ],
            'netcup' => [
                'netcup_client_id' => 'nullable|string|max:255',
                'netcup_client_secret' => 'nullable|string|max:500',
            ],
            default => [],
        };

        $incoming = $request->validate($rules);
        if ($incoming !== []) {
            $this->settings->save($incoming);
        }

        if (! $this->settings->isProviderConfigured($provider)) {
            return redirect()
                ->route('admin.infrastructure.settings.index', ['provider' => $provider])
                ->with('error', 'أكمل بيانات المزود ثم احفظ قبل الاختبار');
        }

        $result = $this->registry->get($provider)->testConnection();

        return redirect()
            ->route('admin.infrastructure.settings.index', ['provider' => $provider])
            ->with(
                ($result['success'] ?? false) ? 'success' : 'error',
                $result['message'] ?? '—'
            );
    }
}
