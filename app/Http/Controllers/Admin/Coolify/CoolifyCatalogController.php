<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyCatalogService;
use App\Services\Coolify\GenericResourceInstallerService;
use App\Services\CoolifyApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class CoolifyCatalogController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyCatalogService $catalog,
        protected CoolifyApiService $coolify,
        protected GenericResourceInstallerService $installer
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
        $allItems = $this->catalog->getCatalog(true, $category ?: null, $search !== '' ? $search : null);
        $perPage = max(12, min(96, (int) config('coolify_catalog.per_page', 48)));
        $page = max(1, (int) $request->get('page', 1));
        $total = count($allItems);
        $slice = array_slice($allItems, ($page - 1) * $perPage, $perPage);

        $items = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.coolify.catalog.index', [
            'items' => $items,
            'totalCount' => $total,
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
            $msg .= ' ('.$result['discovered'].' نوع جديد)';
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
            'extra_payload' => 'nullable|string',
        ]);

        try {
            $result = $this->installer->install($item, $validated);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $response = $result['response'];
        $resourceType = $result['resource_type'];
        $successRoute = $result['success_route'];

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
