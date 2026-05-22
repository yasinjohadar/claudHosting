<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyCatalogService;
use App\Services\CoolifyApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoolifyCatalogController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyCatalogService $catalog,
        protected CoolifyApiService $coolify
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.', 'admin.coolify.settings.index');
        }

        $category = $request->get('category');
        $search = trim((string) $request->get('q', ''));
        $items = $this->catalog->getCatalog(true, $category ?: null, $search !== '' ? $search : null);

        return view('admin.coolify.catalog.index', [
            'items' => $items,
            'categories' => $this->catalog->categories(),
            'category' => $category,
            'search' => $search,
            'configured' => true,
        ]);
    }

    public function sync(): RedirectResponse
    {
        $result = $this->catalog->syncWithCoolify();

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $msg = $result['message'];
        if (($result['discovered'] ?? 0) > 0) {
            $msg .= ' ('.$result['discovered'].' نوع جديد أُضيف معطّلاً — راجع إعدادات الكتالوج)';
        }

        return back()->with('success', $msg);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $item = $this->catalog->findBySlug($slug);
        if (! $item) {
            return $this->coolifyRedirectError('المورد غير موجود.', 'admin.coolify.catalog.index');
        }

        $canInstall = $this->catalog->canInstall($item);

        return view('admin.coolify.catalog.show', compact('item', 'slug', 'canInstall'));
    }

    public function install(Request $request, string $slug): View|RedirectResponse
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.', 'admin.coolify.settings.index');
        }

        $item = $this->catalog->findBySlug($slug);
        if (! $item) {
            return $this->coolifyRedirectError('المورد غير موجود.', 'admin.coolify.catalog.index');
        }

        if (($item['category'] ?? '') === 'application') {
            return redirect()->route('admin.coolify.applications.create', array_filter([
                'type' => $item['coolify_key'] ?? 'public',
                'project_uuid' => $request->get('project_uuid'),
                'server_uuid' => $request->get('server_uuid'),
                'environment_name' => $request->get('environment_name', 'production'),
            ]));
        }

        if (($item['install_mode'] ?? '') === 'link' && ! empty($item['custom_install_url'])) {
            return redirect()->away($item['custom_install_url']);
        }

        if (! $this->catalog->canInstall($item)) {
            return redirect()->route('admin.coolify.catalog.show', $slug)
                ->with('error', 'لا يمكن تثبيت هذا المورد حالياً.');
        }

        $step = max(1, min(3, (int) $request->get('step', 1)));
        if ($step === 3 && (! $request->filled('project_uuid') || ! $request->filled('server_uuid'))) {
            return redirect()->route('admin.coolify.catalog.install', ['slug' => $slug, 'step' => 2])
                ->with('error', 'اختر المشروع والسيرفر أولاً.');
        }

        $projects = $this->coolifyList($this->coolify->listProjects());
        $servers = $this->coolifyList($this->coolify->listServers());

        return view('admin.coolify.catalog.install', compact('item', 'slug', 'projects', 'servers', 'step'));
    }

    public function installStore(Request $request, string $slug): RedirectResponse
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.', 'admin.coolify.settings.index');
        }

        $item = $this->catalog->findBySlug($slug);
        if (! $item || ! $this->catalog->canInstall($item)) {
            return $this->coolifyRedirectError('لا يمكن تثبيت هذا المورد.', 'admin.coolify.catalog.index');
        }

        $validated = $request->validate([
            'project_uuid' => 'required|string',
            'server_uuid' => 'required|string',
            'environment_name' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = $item['category'] ?? '';
        $coolifyKey = $item['coolify_key'] ?? '';

        if ($category === 'database') {
            $type = $coolifyKey;
            $payload = $validated;
            unset($payload['type']);
            $response = $this->coolify->createDatabase($type, $payload);
            $resourceType = 'database';
            $successRoute = 'admin.coolify.databases.show';
        } elseif ($category === 'service' || ($category === 'custom' && ($item['install_mode'] ?? '') === 'service')) {
            $payload = array_merge($validated, ['type' => $coolifyKey]);
            $response = $this->coolify->createService($payload);
            $resourceType = 'service';
            $successRoute = 'admin.coolify.services.show';
        } else {
            return redirect()->route('admin.coolify.catalog.show', $slug)
                ->with('error', 'نوع المورد غير مدعوم للتثبيت المباشر.');
        }

        $this->coolify->clearDashboardCache();

        if (! ($response['success'] ?? false)) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل الإنشاء');
        }

        $created = $this->coolifyItem($response);
        $uuid = $created ? $this->resourceUuid($created) : null;
        $name = $validated['name'] ?? null;

        $this->logCoolify('create', $resourceType, $uuid, $name, 'من كتالوج الموارد: '.$slug);

        if ($uuid) {
            return $this->coolifyRedirectSuccess('تم إنشاء المورد بنجاح', $successRoute, ['uuid' => $uuid]);
        }

        return $this->coolifyRedirectSuccess('تم الإنشاء', str_replace('.show', '.index', $successRoute));
    }
}
