<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyGithubAppController extends Controller
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

        $apps = $this->coolifyList($this->coolify->listGithubApps());
        $error = null;

        return view('admin.coolify.github-apps.index', compact('apps', 'error'));
    }

    public function create()
    {
        return view('admin.coolify.github-apps.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'nullable|url',
            'html_url' => 'nullable|url',
            'custom_user' => 'nullable|string',
            'custom_port' => 'nullable|string',
            'app_id' => 'nullable|string',
            'installation_id' => 'nullable|string',
            'private_key_uuid' => 'nullable|string',
        ]);

        $response = $this->coolify->createGithubApp($validated);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل الإنشاء');
        }

        return $this->coolifyRedirectSuccess('تم ربط GitHub App', 'admin.coolify.github-apps.index');
    }
}
