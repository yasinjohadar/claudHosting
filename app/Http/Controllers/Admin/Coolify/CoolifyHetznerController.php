<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyHetznerController extends Controller
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

        $servers = $this->coolifyList($this->coolify->listServers());
        $cloudTokens = $this->coolifyList($this->coolify->listCloudTokens());

        return view('admin.coolify.hetzner.index', compact('servers', 'cloudTokens'));
    }

    public function create()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $locations = $this->coolifyList($this->coolify->hetznerLocations());
        $serverTypes = $this->coolifyList($this->coolify->hetznerServerTypes());
        $images = $this->coolifyList($this->coolify->hetznerImages());
        $sshKeys = $this->coolifyList($this->coolify->hetznerSshKeys());
        $cloudTokens = $this->coolifyList($this->coolify->listCloudTokens());

        return view('admin.coolify.hetzner.create', compact(
            'locations', 'serverTypes', 'images', 'sshKeys', 'cloudTokens'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cloud_token_uuid' => 'required|string',
            'location' => 'required|string',
            'server_type' => 'required|string',
            'image' => 'required|string',
            'ssh_key_uuid' => 'nullable|string',
        ]);

        $response = $this->coolify->createHetznerServer($validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء السيرفر');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;
        $this->logCoolify('create', 'server', $uuid, $validated['name'] ?? null, 'Hetzner');

        if ($uuid) {
            return $this->coolifyRedirectSuccess('تم إنشاء سيرفر Hetzner', 'admin.coolify.servers.show', ['uuid' => $uuid]);
        }

        return $this->coolifyRedirectSuccess('تم الإنشاء', 'admin.coolify.servers.index');
    }
}
