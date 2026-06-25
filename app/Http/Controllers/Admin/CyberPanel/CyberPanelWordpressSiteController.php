<?php

namespace App\Http\Controllers\Admin\CyberPanel;

use App\Http\Controllers\Controller;
use App\Models\CyberPanelWordpressSite;
use App\Models\User;
use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelWordpressManagementService;
use App\Services\CyberPanel\CyberPanelWordpressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CyberPanelWordpressSiteController extends Controller
{
    public function __construct(
        protected CyberPanelApiService $api,
        protected CyberPanelWordpressService $wordpress,
        protected CyberPanelWordpressManagementService $management
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $sites = CyberPanelWordpressSite::with('website')->latest()->paginate(20);
        $configured = $this->api->isConfigured();
        $supportsCloud = $this->api->supportsCloudOperations();

        return view('admin.cyberpanel.wordpress-sites.index', compact('sites', 'configured', 'supportsCloud'));
    }

    public function show(CyberPanelWordpressSite $wordpressSite)
    {
        $wordpressSite->load(['website.client']);
        $site = $wordpressSite;
        $website = $wordpressSite->website;
        $configured = $this->api->isConfigured();
        $supportsCloud = $this->api->supportsCloudOperations();
        $wpManagementState = $this->management->getManagementState($wordpressSite);
        $wpCanManage = $this->management->canManage($wordpressSite);
        $wpInfo = ($wordpressSite->metadata ?? [])['wp_info'] ?? null;
        $wpExec = $wpManagementState['execute_ready'] ?? false;
        $cpLinks = $this->management->getCyberPanelLinks($wordpressSite);
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();
        $backups = [];
        if ($wpExec) {
            $backupList = $this->api->listWpBackups($wordpressSite->domain);
            if ($backupList['success'] ?? false) {
                $backups = is_array($backupList['data'] ?? null) ? $backupList['data'] : [];
            }
        }
        $sslMeta = is_array($website?->metadata) ? ($website->metadata['ssl'] ?? null) : null;

        return view('admin.cyberpanel.wordpress-sites.show', compact(
            'site',
            'website',
            'configured',
            'supportsCloud',
            'wpManagementState',
            'wpCanManage',
            'wpInfo',
            'wpExec',
            'cpLinks',
            'clientUsers',
            'backups',
            'sslMeta'
        ));
    }

    public function wpInfo(CyberPanelWordpressSite $wordpressSite): JsonResponse
    {
        if (request()->boolean('refresh')) {
            $result = $this->management->executeAction($wordpressSite, 'refresh_info', [], auth()->id());

            return response()->json([
                'success' => $result['success'] ?? false,
                'data' => $result['data'] ?? null,
                'message' => $result['message'] ?? null,
                'can_manage' => $this->management->canManage($wordpressSite->fresh()),
            ]);
        }

        $result = $this->management->getSiteInfo($wordpressSite, false);

        return response()->json([
            'success' => $result['success'] ?? false,
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? null,
            'can_manage' => $this->management->canManage($wordpressSite->fresh()),
        ]);
    }

    public function wpAction(Request $request, CyberPanelWordpressSite $wordpressSite): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|max:64',
            'slug' => 'nullable|string|max:128',
            'backup_file' => 'nullable|string|max:255',
            'source_domain' => 'nullable|string|max:255',
            'wp_core' => 'nullable|string|max:64',
            'plugins' => 'nullable|string|max:32',
            'themes' => 'nullable|string|max:32',
            'include_files' => 'nullable|boolean',
            'include_database' => 'nullable|boolean',
            'include_emails' => 'nullable|boolean',
            'login' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'role' => 'nullable|string|max:32',
            'password' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|min:1',
            'reassign_to' => 'nullable|integer|min:1',
        ]);

        $action = $validated['action'];
        $params = collect($validated)->except('action')->filter(fn ($v) => $v !== null && $v !== '')->all();

        $result = $this->management->executeAction($wordpressSite, $action, $params, auth()->id());
        $fresh = $wordpressSite->fresh();

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'generated_password' => $result['generated_password'] ?? null,
            'login' => $result['login'] ?? null,
            'wp_info' => ($fresh->metadata ?? [])['wp_info'] ?? null,
            'can_manage' => $this->management->canManage($fresh),
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    public function status(CyberPanelWordpressSite $wordpressSite): JsonResponse
    {
        $result = $this->management->getStatus($wordpressSite);

        return response()->json($result);
    }

    public function cyberpanelLinks(CyberPanelWordpressSite $wordpressSite): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->management->getCyberPanelLinks($wordpressSite),
        ]);
    }

    public function installWordpress(Request $request, CyberPanelWordpressSite $wordpressSite)
    {
        $website = $wordpressSite->website;
        if (! $website) {
            return back()->with('error', 'موقع الاستضافة غير مرتبط');
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'admin_user' => 'nullable|string|max:64',
            'admin_email' => 'nullable|email|max:255',
        ]);

        $result = $this->wordpress->installOnWebsite($website, $validated);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function issueSsl(CyberPanelWordpressSite $wordpressSite)
    {
        $website = $wordpressSite->website;
        if (! $website) {
            return back()->with('error', 'موقع الاستضافة غير مرتبط');
        }

        $result = $this->wordpress->issueSslForWebsite($website);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function installWordpressAndSsl(Request $request, CyberPanelWordpressSite $wordpressSite)
    {
        $website = $wordpressSite->website;
        if (! $website) {
            return back()->with('error', 'موقع الاستضافة غير مرتبط');
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'admin_user' => 'nullable|string|max:64',
            'admin_email' => 'nullable|email|max:255',
        ]);

        $result = $this->wordpress->installWordpressAndSsl($website, $validated);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function refreshStatus(CyberPanelWordpressSite $wordpressSite)
    {
        $result = $this->wordpress->refreshInstallStatus($wordpressSite);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function wpAutoLogin(CyberPanelWordpressSite $wordpressSite)
    {
        $payload = $this->wordpress->buildAutoLoginPayload($wordpressSite);

        if (! ($payload['success'] ?? false)) {
            return back()->with('error', $payload['message'] ?? 'تعذّر تسجيل الدخول التلقائي');
        }

        return view('admin.cyberpanel.wordpress-sites.wp-auto-login', [
            'site' => $wordpressSite,
            'loginUrl' => $payload['login_url'],
            'username' => $payload['username'],
            'password' => $payload['password'],
            'redirectTo' => $payload['redirect_to'],
        ]);
    }

    public function saveCredentials(Request $request, CyberPanelWordpressSite $wordpressSite)
    {
        $validated = $request->validate([
            'wp_user' => 'required|string|max:64',
            'wp_password' => 'required|string|max:128',
        ]);

        $result = $this->wordpress->saveAdminCredentials(
            $wordpressSite,
            $validated['wp_user'],
            $validated['wp_password']
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
