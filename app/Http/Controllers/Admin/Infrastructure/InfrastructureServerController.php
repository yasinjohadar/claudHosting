<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Jobs\VpsPowerActionJob;
use App\Models\VpsServer;
use App\Services\CoolifyApiService;
use App\Services\Infrastructure\VpsActionService;
use App\Services\Infrastructure\VpsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfrastructureServerController extends Controller
{
    public function __construct(
        protected VpsSyncService $sync,
        protected VpsActionService $actions,
        protected CoolifyApiService $coolify
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $query = VpsServer::query()
            ->with('latestMetricSnapshot')
            ->latest('last_synced_at');

        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', $q)
                    ->orWhere('ip', 'like', $q)
                    ->orWhere('external_id', 'like', $q);
            });
        }

        return view('admin.infrastructure.servers.index', [
            'servers' => $query->paginate(25)->withQueryString(),
            'providers' => VpsServer::PROVIDERS,
            'filters' => $request->only(['provider', 'status', 'q']),
        ]);
    }

    public function show(string $uuid): View|RedirectResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $server->load(['actionLogs' => fn ($q) => $q->with('user')->latest()->limit(30)]);

        $coolifyServers = [];
        if ($this->coolify->isConfigured()) {
            $coolifyServers = $this->coolify->normalizeList($this->coolify->listServers()['data'] ?? []);
        }

        return view('admin.infrastructure.servers.show', compact('server', 'coolifyServers'));
    }

    public function edit(string $uuid): View
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $coolifyServers = [];
        if ($this->coolify->isConfigured()) {
            $coolifyServers = $this->coolify->normalizeList($this->coolify->listServers()['data'] ?? []);
        }

        return view('admin.infrastructure.servers.edit', compact('server', 'coolifyServers'));
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'coolify_server_uuid' => 'nullable|string|max:64',
        ]);

        $server->update([
            'name' => $validated['name'],
            'coolify_server_uuid' => $validated['coolify_server_uuid'] ?: null,
        ]);

        return redirect()
            ->route('admin.infrastructure.servers.show', $server->uuid)
            ->with('success', 'تم تحديث السيرفر');
    }

    public function sync(Request $request): RedirectResponse
    {
        $provider = $request->input('provider');
        $result = $this->sync->syncAll(is_string($provider) && $provider !== '' ? $provider : null);

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? '—'
        );
    }

    public function refresh(string $uuid): RedirectResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $result = $this->sync->refreshOne($server);

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? '—'
        );
    }

    public function power(Request $request, string $uuid, string $action): RedirectResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();

        if ($action === 'stop' && ! $request->boolean('confirm_stop')) {
            return back()->with('error', 'يجب تأكيد الإيقاف — قد يؤدي قطع التيار إلى فقدان بيانات غير محفوظة.');
        }

        if ($request->boolean('async')) {
            VpsPowerActionJob::dispatch($server, $action, auth()->id());

            return back()->with('success', 'تم إرسال الأمر — سيتم تحديث الحالة خلال ثوانٍ');
        }

        $result = $this->actions->execute($server, $action, auth()->user());
        $this->sync->refreshOne($server->fresh());

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? '—'
        );
    }
}
