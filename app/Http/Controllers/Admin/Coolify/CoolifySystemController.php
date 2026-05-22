<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifySystemController extends Controller
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

        $version = $this->coolify->getVersion();
        $health = $this->coolify->getHealth();
        $resources = $this->coolifyList($this->coolify->getSystemResources());

        return view('admin.coolify.system.index', compact('version', 'health', 'resources'));
    }

    public function enableApi(Request $request)
    {
        $response = $this->coolify->enableApi();
        if ($response['success']) {
            $this->logCoolify('enable_api', 'system', null, null, 'تفعيل API');
        }

        return back()->with($response['success'] ? 'success' : 'error', $response['message'] ?? ($response['success'] ? 'تم التفعيل' : 'فشل'));
    }

    public function disableApi(Request $request)
    {
        $response = $this->coolify->disableApi();
        if ($response['success']) {
            $this->logCoolify('disable_api', 'system', null, null, 'تعطيل API');
        }

        return back()->with($response['success'] ? 'success' : 'error', $response['message'] ?? ($response['success'] ? 'تم التعطيل' : 'فشل'));
    }
}
