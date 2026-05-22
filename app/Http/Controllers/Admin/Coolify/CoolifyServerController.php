<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyServerController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(protected CoolifyApiService $coolify)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $response = $this->coolify->listServers();
        $servers = $this->coolifyList($response);
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب السيرفرات');

        return view('admin.coolify.servers.index', compact('servers', 'error'));
    }

    public function create()
    {
        $keysResponse = $this->coolify->listPrivateKeys();
        $privateKeys = $this->coolifyList($keysResponse);

        return view('admin.coolify.servers.create', compact('privateKeys'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'user' => 'nullable|string|max:255',
            'private_key_uuid' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['port'] = $validated['port'] ?? 22;
        $validated['user'] = $validated['user'] ?? 'root';

        $response = $this->coolify->createServer($validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء السيرفر');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            $this->logCoolify('create', 'server', $uuid, $validated['name'] ?? null);

            return $this->coolifyRedirectSuccess('تم إنشاء السيرفر بنجاح', 'admin.coolify.servers.show', ['uuid' => $uuid]);
        }

        $this->logCoolify('create', 'server', null, $validated['name'] ?? null);

        return $this->coolifyRedirectSuccess('تم إنشاء السيرفر', 'admin.coolify.servers.index');
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getServer($uuid);
        $server = $this->coolifyItem($response);

        if (! $server) {
            return $this->coolifyRedirectError($response['message'] ?? 'السيرفر غير موجود', 'admin.coolify.servers.index');
        }

        return view('admin.coolify.servers.show', compact('server', 'uuid'));
    }

    public function edit(string $uuid)
    {
        $response = $this->coolify->getServer($uuid);
        $server = $this->coolifyItem($response);

        if (! $server) {
            return $this->coolifyRedirectError($response['message'] ?? 'السيرفر غير موجود', 'admin.coolify.servers.index');
        }

        return view('admin.coolify.servers.edit', compact('server', 'uuid'));
    }

    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ip' => 'sometimes|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'user' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $response = $this->coolify->updateServer($uuid, $validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم تحديث السيرفر', 'admin.coolify.servers.show', ['uuid' => $uuid]);
    }

    public function destroy(string $uuid)
    {
        $get = $this->coolify->getServer($uuid);
        $server = $this->coolifyItem($get);

        if ($server && ! empty($server['is_coolify_host'])) {
            return redirect()->route('admin.coolify.servers.show', $uuid)
                ->with('error', 'لا يمكن حذف سيرفر Coolify الرئيسي (localhost)');
        }

        $response = $this->coolify->deleteServer($uuid);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return redirect()->route('admin.coolify.servers.show', $uuid)
                ->with('error', $response['message'] ?? 'فشل الحذف');
        }

        $this->logCoolify('delete', 'server', $uuid, $server['name'] ?? null);

        return $this->coolifyRedirectSuccess('تم حذف السيرفر', 'admin.coolify.servers.index');
    }

    public function validateConnection(string $uuid)
    {
        $response = $this->coolify->validateServer($uuid);

        return view('admin.coolify.servers.validate', [
            'uuid' => $uuid,
            'result' => $response,
            'data' => $response['data'] ?? null,
        ]);
    }

    public function resources(string $uuid)
    {
        $response = $this->coolify->serverResources($uuid);
        $resources = $this->coolifyList($response);

        return view('admin.coolify.servers.resources', compact('uuid', 'resources', 'response'));
    }

    public function domains(string $uuid)
    {
        $response = $this->coolify->serverDomains($uuid);
        $domains = $this->coolifyList($response);
        if (empty($domains) && isset($response['data']) && is_array($response['data']) && ! array_is_list($response['data'])) {
            $domains = $response['data'];
        }

        return view('admin.coolify.servers.domains', compact('uuid', 'domains', 'response'));
    }
}
