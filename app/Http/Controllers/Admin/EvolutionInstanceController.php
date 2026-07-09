<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvolutionInstance;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EvolutionInstanceController extends Controller
{
    public function __construct(
        private EvolutionService $evolutionService,
        private WhatsAppSettingsService $settingsService,
    ) {}

    public function index(): View
    {
        $settings = $this->settingsService->getSettings();
        $instances = EvolutionInstance::orderByDesc('is_default')
            ->orderByDesc('is_manual')
            ->orderBy('instance_name')
            ->get();

        return view('admin.pages.evolution-api.instances.index', [
            'instances' => $instances,
            'settings' => $settings,
            'hasApiKey' => ($settings['evolution_api_key'] ?? '') !== '',
            'error' => null,
            'rotationPoolCount' => EvolutionInstance::rotationPoolCount(),
            'defaultInstanceName' => $settings['evolution_instance_name'] ?? '',
            'connectedCount' => $instances->filter(fn ($i) => $i->isConnected())->count(),
        ]);
    }

    public function testConnection(Request $request): JsonResponse
    {
        $request->validate([
            'evolution_base_url' => ['nullable', 'string', 'max:500'],
            'evolution_api_key' => ['nullable', 'string', 'max:500'],
            'instance_name' => ['required', 'string', 'max:150'],
        ]);

        $existing = $this->settingsService->getSettings();
        $config = [
            'base_url' => $request->input('evolution_base_url') ?: ($existing['evolution_base_url'] ?? ''),
            'api_key' => $request->input('evolution_api_key') ?: ($existing['evolution_api_key'] ?? ''),
            'instance_name' => trim((string) $request->input('instance_name')),
        ];

        $provider = \App\Services\WhatsApp\WhatsAppProviderFactory::create('evolution', $config);

        return response()->json($provider->testConnection());
    }

    public function saveConnection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evolution_base_url' => ['required', 'string', 'max:500'],
            'evolution_api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $existing = $this->settingsService->getSettings();
        if (empty($validated['evolution_api_key'])) {
            $validated['evolution_api_key'] = $existing['evolution_api_key'] ?? '';
        }

        $this->settingsService->updateSettings([
            'evolution_base_url' => rtrim(trim($validated['evolution_base_url']), '/'),
            'evolution_api_key' => $validated['evolution_api_key'],
            'whatsapp_enabled' => '1',
            'whatsapp_provider' => 'evolution',
        ]);

        return back()->with('success', 'تم حفظ بيانات الاتصال العامة (Base URL + API Key). لم يُحذف أي Instance.');
    }

    public function registerManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instance_name' => ['required', 'string', 'max:150'],
            'label' => ['nullable', 'string', 'max:150'],
            'evolution_base_url' => ['nullable', 'string', 'max:500'],
            'evolution_api_key' => ['nullable', 'string', 'max:500'],
            'verify_connection' => ['nullable', 'boolean'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);

        try {
            $instance = $this->evolutionService->registerManualInstance([
                'instance_name' => $validated['instance_name'],
                'label' => $validated['label'] ?? null,
                'evolution_base_url' => $validated['evolution_base_url'] ?? null,
                'evolution_api_key' => $validated['evolution_api_key'] ?? null,
                'verify' => $request->boolean('verify_connection'),
                'set_as_default' => false,
            ]);

            $message = 'تمت إضافة Instance «'.$instance->instance_name.'» إلى القائمة. عيّن الافتراضي من الجدول أدناه عند الحاجة.';

            return back()->with('success', $message);
        } catch (Throwable $e) {
            return back()->with('error', EvolutionApiException::resolveUserMessage($e));
        }
    }

    public function registerBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instance_names' => ['required', 'string', 'max:5000'],
            'set_as_default_first' => ['nullable', 'boolean'],
        ]);

        $names = $this->evolutionService->parseInstanceNamesList($validated['instance_names']);
        if ($names === []) {
            return back()->with('error', 'أدخل اسم instance واحداً على الأقل (سطر لكل اسم).');
        }

        $added = 0;

        foreach ($names as $name) {
            try {
                $this->evolutionService->registerManualInstance([
                    'instance_name' => $name,
                    'verify' => false,
                    'set_as_default' => false,
                ]);
                $added++;
            } catch (Throwable) {
                continue;
            }
        }

        return back()->with('success', 'تمت إضافة '.$added.' instance يدوياً. يمكنك تحديث حالتها لاحقاً أو مسح QR لكل واحد.');
    }

    public function connect(string $instanceName): View
    {
        $instance = EvolutionInstance::where('instance_name', $instanceName)->firstOrFail();
        $settings = $this->evolutionService->getSettings();

        return view('admin.pages.evolution-api.instances.connect', compact('instance', 'settings'));
    }

    public function fetchQr(string $instanceName): JsonResponse
    {
        try {
            $response = $this->evolutionService->clientFor(null, $instanceName)->connectInstance($instanceName);
            $qr = $response['base64'] ?? $response['qrcode']['base64'] ?? $response['code'] ?? null;

            if ($qr) {
                EvolutionInstance::where('instance_name', $instanceName)->update([
                    'qr_code' => $qr,
                    'connection_status' => 'connecting',
                ]);
            }

            return response()->json([
                'success' => true,
                'qr' => $qr,
                'raw' => $response,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => EvolutionApiException::resolveUserMessage($e),
            ], 422);
        }
    }

    public function status(string $instanceName): JsonResponse
    {
        try {
            $instance = EvolutionInstance::where('instance_name', $instanceName)->firstOrFail();
            $this->evolutionService->refreshInstanceFromApi($instance);
            $this->evolutionService->syncInstances(false);

            $fresh = $instance->fresh();

            return response()->json([
                'success' => true,
                'state' => $fresh->connection_status,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => EvolutionApiException::resolveUserMessage($e),
            ], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instanceName' => ['required', 'string', 'max:150'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);

        $instanceName = trim($validated['instanceName']);

        try {
            $this->evolutionService->clientFor(null, $instanceName)->createInstance([
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
            ]);

            $this->evolutionService->registerManualInstance([
                'instance_name' => $instanceName,
                'verify' => false,
                'set_as_default' => $request->boolean('set_as_default'),
            ]);

            $this->evolutionService->syncInstances($request->boolean('set_as_default'));

            $message = 'تم إنشاء Instance «'.$instanceName.'». امسح QR للربط.';
            if ($request->boolean('set_as_default')) {
                $message .= ' وتم تعيينه كافتراضي.';
            }

            return redirect()
                ->route('admin.evolution-api.instances.connect', ['instanceName' => $instanceName])
                ->with('success', $message);
        } catch (Throwable $e) {
            return $this->evolutionErrorRedirect($e);
        }
    }

    public function link(Request $request): RedirectResponse
    {
        $request->merge([
            'instance_name' => trim((string) $request->input('instanceName', $request->input('instance_name', ''))),
            'verify_connection' => true,
        ]);

        return $this->registerManual($request);
    }

    public function setDefault(string $instanceName): RedirectResponse
    {
        $instance = EvolutionInstance::where('instance_name', $instanceName)->firstOrFail();

        try {
            $this->evolutionService->assignDefaultInstance($instance->instance_name);

            return back()->with('success', 'تم تعيين «'.$instance->instance_name.'» كـ Instance افتراضي.');
        } catch (Throwable $e) {
            return $this->evolutionErrorRedirect($e);
        }
    }

    public function restart(string $instanceName): RedirectResponse
    {
        try {
            $this->evolutionService->clientFor(null, $instanceName)->restartInstance($instanceName);

            return back()->with('success', 'تم إعادة تشغيل Instance.');
        } catch (Throwable $e) {
            return $this->evolutionErrorRedirect($e);
        }
    }

    public function logout(string $instanceName): RedirectResponse
    {
        try {
            $this->evolutionService->clientFor(null, $instanceName)->logoutInstance($instanceName);
            $this->evolutionService->syncInstances(false);

            return back()->with('success', 'تم تسجيل الخروج من Instance.');
        } catch (Throwable $e) {
            return $this->evolutionErrorRedirect($e);
        }
    }

    public function destroy(string $instanceName): RedirectResponse
    {
        $instance = EvolutionInstance::where('instance_name', $instanceName)->first();
        $remoteDeleted = false;
        $remoteAlreadyGone = false;

        if (! $instance?->is_manual) {
            try {
                $this->evolutionService->clientFor($instance, $instanceName)->deleteInstance($instanceName);
                $remoteDeleted = true;
            } catch (Throwable $e) {
                if (EvolutionApiException::isNotFound($e)) {
                    $remoteAlreadyGone = true;
                } elseif ($instance) {
                    return $this->evolutionErrorRedirect($e);
                }
            }
        }

        $wasDefault = (bool) ($instance?->is_default);
        $settingsName = $this->settingsService->getSettings()['evolution_instance_name'] ?? '';

        EvolutionInstance::where('instance_name', $instanceName)->delete();

        if ($wasDefault || $settingsName === $instanceName) {
            $replacement = EvolutionInstance::orderByDesc('is_default')
                ->orderByDesc('connection_status')
                ->orderBy('instance_name')
                ->first();

            if ($replacement) {
                $this->evolutionService->assignDefaultInstance($replacement->instance_name);
            } elseif ($settingsName === $instanceName) {
                $this->settingsService->updateSettings(['evolution_instance_name' => '']);
            }
        }

        $message = match (true) {
            $remoteDeleted => 'تم حذف Instance من Evolution والمنصة.',
            $remoteAlreadyGone => 'تم إزالة Instance من المنصة (لم يعد موجوداً على Evolution API).',
            default => 'تم حذف Instance من المنصة.',
        };

        return redirect()->route('admin.evolution-api.instances.index')->with('success', $message);
    }

    public function sync(): RedirectResponse
    {
        try {
            $refreshed = $this->evolutionService->syncAllRegisteredInstances();
            $discovered = $this->evolutionService->syncInstances(false);
            $poolCount = EvolutionInstance::rotationPoolCount();

            $message = 'تمت مزامنة '.count($refreshed).' instance مسجّل';
            if (count($discovered) > 0) {
                $message .= ' واكتشاف '.count($discovered).' من Evolution API';
            }
            $message .= '. جلسات التبديل النشطة: '.$poolCount.'.';

            if ($poolCount < 2) {
                $message .= ' لإتاحة التبديل بين رقمين، اربط instance إضافياً عبر QR حتى تصبح حالته open.';
            }

            return back()->with($poolCount >= 2 ? 'success' : 'warning', $message);
        } catch (Throwable $e) {
            return $this->evolutionErrorRedirect($e);
        }
    }

    public function toggleRotation(string $instanceName): RedirectResponse
    {
        $instance = EvolutionInstance::where('instance_name', $instanceName)->firstOrFail();
        $instance->update(['rotation_enabled' => ! $instance->rotation_enabled]);

        $status = $instance->rotation_enabled ? 'مفعّل' : 'معطّل';

        return back()->with('success', 'تم '.$status.' مشاركة «'.$instance->instance_name.'» في التبديل التلقائي.');
    }

    private function evolutionErrorRedirect(Throwable $e): RedirectResponse
    {
        return back()->with('error', EvolutionApiException::resolveUserMessage($e));
    }
}
