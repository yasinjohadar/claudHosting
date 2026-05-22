<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyCloudTokenController extends Controller
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

        $tokens = $this->coolifyList($this->coolify->listCloudTokens());

        return view('admin.coolify.cloud-tokens.index', compact('tokens'));
    }

    public function create()
    {
        return view('admin.coolify.cloud-tokens.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'token' => 'required|string',
        ]);

        $response = $this->coolify->createCloudToken($validated);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل الإنشاء');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            return redirect()->route('admin.coolify.cloud-tokens.show', $uuid)->with('success', 'تم إنشاء التوكن');
        }

        return $this->coolifyRedirectSuccess('تم الإنشاء', 'admin.coolify.cloud-tokens.index');
    }

    public function show(string $uuid)
    {
        $validate = $this->coolify->validateCloudToken($uuid);

        return view('admin.coolify.cloud-tokens.show', compact('uuid', 'validate'));
    }

    public function validateToken(string $uuid)
    {
        $response = $this->coolify->validateCloudToken($uuid);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل التحقق');
        }

        $this->logCoolify('validate', 'cloud_token', $uuid);

        return back()->with('success', 'التوكن صالح');
    }
}
