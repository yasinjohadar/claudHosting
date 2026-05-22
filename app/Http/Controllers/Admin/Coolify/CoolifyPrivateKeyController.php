<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyPrivateKeyController extends Controller
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

        $response = $this->coolify->listPrivateKeys();
        $keys = $this->coolifyList($response);
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب المفاتيح');

        return view('admin.coolify.private-keys.index', compact('keys', 'error'));
    }

    public function create()
    {
        return view('admin.coolify.private-keys.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'private_key' => 'required|string',
        ]);

        $response = $this->coolify->createPrivateKey($validated);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل الإنشاء');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            return $this->coolifyRedirectSuccess('تم إنشاء المفتاح', 'admin.coolify.private-keys.show', ['uuid' => $uuid]);
        }

        return $this->coolifyRedirectSuccess('تم الإنشاء', 'admin.coolify.private-keys.index');
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getPrivateKey($uuid);
        $key = $this->coolifyItem($response);

        if (! $key) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.private-keys.index');
        }

        return view('admin.coolify.private-keys.show', compact('key', 'uuid'));
    }

    public function edit(string $uuid)
    {
        $response = $this->coolify->getPrivateKey($uuid);
        $key = $this->coolifyItem($response);

        if (! $key) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.private-keys.index');
        }

        return view('admin.coolify.private-keys.edit', compact('key', 'uuid'));
    }

    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'private_key' => 'nullable|string',
        ]);

        $response = $this->coolify->updatePrivateKey($uuid, array_filter($validated));

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم التحديث', 'admin.coolify.private-keys.show', ['uuid' => $uuid]);
    }

    public function destroy(string $uuid)
    {
        $response = $this->coolify->deletePrivateKey($uuid);

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل الحذف', 'admin.coolify.private-keys.show');
        }

        return $this->coolifyRedirectSuccess('تم الحذف', 'admin.coolify.private-keys.index');
    }
}
