<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;

class CoolifyResourceController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(protected CoolifyApiService $coolify)
    {
        $this->middleware('auth');
    }

    public function index(\Illuminate\Http\Request $request)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $response = $this->coolify->listResources();
        $resources = $this->coolifyList($response);
        $error = $response['success'] ? null : ($response['message'] ?? null);

        $q = strtolower(trim((string) $request->query('q', '')));
        if ($q !== '') {
            $resources = array_values(array_filter($resources, function (array $r) use ($q) {
                $hay = strtolower(implode(' ', [
                    (string) ($r['name'] ?? ''),
                    (string) ($r['uuid'] ?? ''),
                    (string) ($r['type'] ?? ''),
                    (string) ($r['project_name'] ?? $r['project_uuid'] ?? ''),
                ]));

                return str_contains($hay, $q);
            }));
        }

        return view('admin.coolify.resources.index', compact('resources', 'error', 'q'));
    }
}
