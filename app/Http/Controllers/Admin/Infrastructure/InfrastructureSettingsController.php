<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\VpsServer;
use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\NetcupDeviceAuthService;
use App\Services\Infrastructure\VpsProviderRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InfrastructureSettingsController extends Controller
{
    public function __construct(
        protected InfrastructureSettingsService $settings,
        protected VpsProviderRegistry $registry,
        protected NetcupDeviceAuthService $netcupDeviceAuth
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
                'netcup_customer_number' => 'nullable|string|max:32',
                'netcup_api_password' => 'nullable|string|max:500',
                'netcup_refresh_token' => 'nullable|string|max:4000',
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
                'netcup_customer_number' => 'nullable|string|max:32',
                'netcup_api_password' => 'nullable|string|max:500',
                'netcup_refresh_token' => 'nullable|string|max:4000',
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

    public function netcupDeviceStart(Request $request): JsonResponse
    {
        $result = $this->netcupDeviceAuth->start((int) $request->user()->id);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function netcupDevicePoll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'poll_token' => ['required', 'string', 'max:80'],
        ]);

        $result = $this->netcupDeviceAuth->poll($data['poll_token'], (int) $request->user()->id);

        $status = match ($result['status'] ?? '') {
            'success' => 200,
            'pending' => 202,
            default => 422,
        };

        return response()->json($result, $status);
    }

    public function netcupRevoke(Request $request): JsonResponse
    {
        $result = $this->netcupDeviceAuth->revoke();

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
