<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Concerns\ResolvesAuthorizedWordpressSite;
use App\Http\Controllers\Controller;
use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\FilebrowserCredentialService;
use App\Services\Coolify\FilebrowserProxyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CoolifyWordpressSiteFilebrowserController extends Controller
{
    use ResolvesAuthorizedWordpressSite;

    public function __construct(
        protected FilebrowserProxyService $proxy,
        protected FilebrowserCredentialService $credentials,
        protected CoolifySettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function show(Request $request, string $uuid)
    {
        $site = $this->site($uuid);

        if (! $this->proxy->canEmbed($site)) {
            return redirect()
                ->route($this->panelShowRoute($request), $uuid)
                ->with('error', 'FileBrowser غير جاهز على هذا الموقع.');
        }

        $this->credentials->ensureCredentials($site);
        $site = $site->fresh();

        $this->proxy->warmSession($site, (int) Auth::id());

        $openMode = $this->settings->getWordpressFilebrowserOpenMode();
        $externalUrl = $this->proxy->upstreamBaseUrl($site);

        $panel = str_starts_with($request->route()?->getName() ?? '', 'client.') ? 'client' : 'admin';
        $proxyUrl = route("{$this->routePrefix($request)}.filebrowser.proxy", ['uuid' => $uuid, 'path' => 'files/']);

        return view('admin.coolify.wordpress-sites.filebrowser', [
            'site' => $site,
            'proxyUrl' => $proxyUrl,
            'externalUrl' => $externalUrl,
            'openMode' => $openMode,
            'panel' => $panel,
            'backUrl' => route($this->panelShowRoute($request), $uuid),
        ]);
    }

    public function proxy(Request $request, string $uuid, ?string $path = null): Response
    {
        $site = $this->site($uuid);
        $userId = (int) Auth::id();

        return $this->proxy->proxy($request, $site, $userId, $path);
    }

    public function rotateCredentials(Request $request, string $uuid)
    {
        $site = $this->site($uuid);
        $result = $this->credentials->rotate($site);

        if ($request->expectsJson()) {
            return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
        }

        return back()->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            ($result['ok'] ?? false) ? 'تم إعادة تعيين بيانات FileBrowser.' : ($result['message'] ?? 'فشل إعادة التعيين')
        );
    }

    protected function site(string $uuid): CoolifyWordpressSite
    {
        return $this->resolveAuthorizedWordpressSite($uuid);
    }

    protected function routePrefix(Request $request): string
    {
        return str_starts_with($request->route()?->getName() ?? '', 'client.')
            ? 'client.wordpress-sites'
            : 'admin.coolify.wordpress-sites';
    }

    protected function panelShowRoute(Request $request): string
    {
        return $this->routePrefix($request).'.show';
    }
}
