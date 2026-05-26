<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class FilebrowserProxyService
{
    protected const SESSION_TTL_SECONDS = 7200;

    public function __construct(
        protected FilebrowserCredentialService $credentials,
        protected CoolifySettingsService $settings
    ) {}

    public function upstreamBaseUrl(CoolifyWordpressSite $site): ?string
    {
        $metadata = $site->metadata ?? [];
        $url = trim((string) ($metadata['filebrowser_coolify_url'] ?? $metadata['filebrowser_url'] ?? ''));
        if ($url === '') {
            $url = trim((string) ($metadata['filebrowser_custom_url'] ?? ''));
        }

        return $url !== '' ? rtrim($url, '/') : null;
    }

    public function canEmbed(CoolifyWordpressSite $site): bool
    {
        if (($site->status ?? '') !== 'running') {
            return false;
        }

        if (($site->metadata['filebrowser_healthy'] ?? null) === false) {
            return false;
        }

        return $this->upstreamBaseUrl($site) !== null;
    }

    public function proxy(Request $request, CoolifyWordpressSite $site, int $userId, ?string $path = null): Response
    {
        $upstream = $this->upstreamBaseUrl($site);
        if ($upstream === null) {
            return response('FileBrowser غير متاح على هذا الموقع.', 503);
        }

        if (! $this->credentials->hasStoredCredentials($site->metadata ?? [])) {
            $sync = $this->credentials->ensureCredentials($site);
            if (! ($sync['ok'] ?? false)) {
                return response($sync['message'] ?? 'تعذّر تجهيز بيانات الدخول', 503);
            }
            $site = $site->fresh();
        }

        $session = $this->getOrCreateSession($site, $userId, $upstream);
        if ($session === null) {
            return response('فشل تسجيل الدخول إلى FileBrowser', 502);
        }

        $path = $path ?? $request->route('path') ?? '';
        $path = ltrim((string) $path, '/');
        $target = $upstream.'/'.($path !== '' ? $path : '');
        if ($request->getQueryString()) {
            $target .= '?'.$request->getQueryString();
        }

        $proxyBase = $this->proxyBaseUrl($request, $site);
        $headers = $this->forwardRequestHeaders($request);
        $headers['Authorization'] = 'Bearer '.$session['token'];

        $pending = Http::withHeaders($headers)
            ->withOptions(['allow_redirects' => false])
            ->timeout(120);

        $method = strtoupper($request->method());
        $body = in_array($method, ['GET', 'HEAD'], true) ? null : $request->getContent();

        if ($body !== null && $body !== '') {
            $response = $pending->withBody($body, $request->header('Content-Type', 'application/octet-stream'))
                ->send($method, $target);
        } else {
            $response = $pending->send($method, $target);
        }

        if (in_array($response->status(), [401, 403], true)) {
            $this->forgetSession($site, $userId);
            $session = $this->getOrCreateSession($site->fresh(), $userId, $upstream);
            if ($session === null) {
                return response('فشل إعادة المصادقة مع FileBrowser', 502);
            }
            $headers['Authorization'] = 'Bearer '.$session['token'];
            $pending = Http::withHeaders($headers)->withOptions(['allow_redirects' => false])->timeout(120);
            $response = $body !== null && $body !== ''
                ? $pending->withBody($body, $request->header('Content-Type', 'application/octet-stream'))->send($method, $target)
                : $pending->send($method, $target);
        }

        return $this->buildProxiedResponse($request, $response, $upstream, $proxyBase);
    }

    /**
     * @return array{token: string}|null
     */
    protected function getOrCreateSession(CoolifyWordpressSite $site, int $userId, string $upstream): ?array
    {
        $cacheKey = $this->sessionCacheKey($site, $userId);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && filled($cached['token'] ?? null)) {
            return $cached;
        }

        $creds = $this->credentials->getCredentials($site);
        if ($creds === null) {
            return null;
        }

        $login = Http::asJson()
            ->timeout(30)
            ->post($upstream.'/api/login', [
                'username' => $creds['username'],
                'password' => $creds['password'],
            ]);

        if (! $login->successful()) {
            return null;
        }

        $token = (string) ($login->json('token') ?? '');
        if ($token === '') {
            return null;
        }

        $session = ['token' => $token];
        Cache::put($cacheKey, $session, self::SESSION_TTL_SECONDS);

        return $session;
    }

    protected function forgetSession(CoolifyWordpressSite $site, int $userId): void
    {
        Cache::forget($this->sessionCacheKey($site, $userId));
    }

    protected function sessionCacheKey(CoolifyWordpressSite $site, int $userId): string
    {
        return 'filebrowser_session:'.$site->uuid.':'.$userId;
    }

    protected function proxyBaseUrl(Request $request, CoolifyWordpressSite $site): string
    {
        $prefix = str_starts_with($request->route()?->getName() ?? '', 'client.')
            ? 'client.wordpress-sites'
            : 'admin.coolify.wordpress-sites';

        return rtrim(route("{$prefix}.filebrowser.proxy", ['uuid' => $site->uuid, 'path' => '']), '/');
    }

    /**
     * @return array<string, string>
     */
    protected function forwardRequestHeaders(Request $request): array
    {
        $headers = [];
        foreach (['Accept', 'Accept-Language', 'Content-Type', 'Range', 'If-None-Match', 'If-Modified-Since'] as $name) {
            $value = $request->header($name);
            if ($value !== null && $value !== '') {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * @param  \Illuminate\Http\Client\Response  $upstreamResponse
     */
    protected function buildProxiedResponse(
        Request $request,
        $upstreamResponse,
        string $upstreamOrigin,
        string $proxyBase
    ): Response {
        $status = $upstreamResponse->status();
        $contentType = (string) $upstreamResponse->header('Content-Type');
        $body = $upstreamResponse->body();

        $upstreamOrigin = rtrim($upstreamOrigin, '/');
        $proxyBase = rtrim($proxyBase, '/');

        if ($this->shouldRewriteBody($contentType)) {
            $body = str_replace($upstreamOrigin, $proxyBase, $body);
            $body = preg_replace('#(https?:)?//[^/\'"]+/api/#', $proxyBase.'/api/', $body) ?? $body;
        }

        $response = response($body, $status);

        foreach ($upstreamResponse->headers() as $name => $values) {
            $lower = strtolower($name);
            if (in_array($lower, [
                'transfer-encoding',
                'content-encoding',
                'content-length',
                'connection',
                'x-frame-options',
                'content-security-policy',
                'content-security-policy-report-only',
            ], true)) {
                continue;
            }
            foreach ($values as $value) {
                if ($lower === 'location') {
                    $value = str_replace($upstreamOrigin, $proxyBase, $value);
                }
                $response->headers->set($name, $value, false);
            }
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', true);
        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');

        if ($contentType !== '') {
            $response->headers->set('Content-Type', $contentType);
        }

        return $response;
    }

    protected function shouldRewriteBody(string $contentType): bool
    {
        $contentType = strtolower($contentType);

        return str_contains($contentType, 'text/html')
            || str_contains($contentType, 'javascript')
            || str_contains($contentType, 'json')
            || str_contains($contentType, 'text/css');
    }
}
