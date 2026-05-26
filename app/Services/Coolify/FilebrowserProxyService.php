<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FilebrowserProxyService
{
    protected const SESSION_TTL_SECONDS = 7200;

    public function __construct(
        protected FilebrowserCredentialService $credentials,
        protected FilebrowserUpstreamResolver $upstreamResolver
    ) {}

    public function upstreamBaseUrl(CoolifyWordpressSite $site, bool $refresh = false): ?string
    {
        return $this->upstreamResolver->resolve($site, $refresh);
    }

    public function warmSession(CoolifyWordpressSite $site, int $userId): bool
    {
        $upstream = $this->upstreamBaseUrl($site) ?? $this->upstreamBaseUrl($site, refresh: true);
        if ($upstream === null) {
            return false;
        }

        return $this->getOrCreateSession($site, $userId, $upstream) !== null;
    }

    public function canEmbed(CoolifyWordpressSite $site): bool
    {
        if (($site->status ?? '') !== 'running') {
            return false;
        }

        if (($site->metadata['filebrowser_healthy'] ?? null) === false) {
            return false;
        }

        $meta = $site->metadata ?? [];
        if (! ($meta['filebrowser_enabled'] ?? false)) {
            return false;
        }

        foreach (['filebrowser_coolify_url', 'filebrowser_url', 'filebrowser_custom_url', 'filebrowser_upstream_url'] as $key) {
            if (filled($meta[$key] ?? null)) {
                return true;
            }
        }

        return $this->upstreamResolver->publicCandidateUrls($site) !== [];
    }

    public function proxy(Request $request, CoolifyWordpressSite $site, int $userId, ?string $path = null): Response
    {
        if (! $this->credentials->hasStoredCredentials($site->metadata ?? [])) {
            set_time_limit(120);
            $sync = $this->credentials->ensureCredentials($site);
            if (! ($sync['ok'] ?? false)) {
                return $this->errorResponse(
                    ($sync['message'] ?? 'تعذّر تجهيز بيانات الدخول')
                    .' — نفّذ: php artisan wordpress-sites:sync-filebrowser-credentials --slug='.$site->slug,
                    503
                );
            }
            $site = $site->fresh();
        }

        $upstream = $this->upstreamBaseUrl($site);
        if ($upstream === null) {
            $upstream = $this->upstreamBaseUrl($site, refresh: true);
        }
        if ($upstream === null) {
            return $this->errorResponse(
                'تعذّر الاتصال بـ FileBrowser من اللوحة. تحقق من تشغيل الحاوية أو نفّذ: php artisan wordpress-sites:sync-filebrowser-credentials --slug='.$site->slug,
                503
            );
        }

        $session = $this->getOrCreateSession($site, $userId, $upstream);
        if ($session === null) {
            $this->upstreamResolver->resolve($site, refresh: true);
            $upstream = $this->upstreamBaseUrl($site, refresh: true) ?? $upstream;
            $this->forgetSession($site, $userId);
            $session = $this->getOrCreateSession($site->fresh(), $userId, $upstream);
        }
        if ($session === null) {
            return $this->errorResponse('فشل تسجيل الدخول إلى FileBrowser. جرّب «إعادة تعيين بيانات الدخول» من نفس الصفحة.', 502);
        }

        $path = ltrim((string) ($path ?? $request->route('path') ?? ''), '/');
        $target = rtrim($upstream, '/').'/'.($path !== '' ? $path : '');
        if ($request->getQueryString()) {
            $target .= '?'.$request->getQueryString();
        }

        $proxyBase = $this->proxyBaseUrl($request, $site);
        $headers = $this->forwardRequestHeaders($request);
        $headers['Authorization'] = 'Bearer '.$session['token'];
        $headers['Cookie'] = 'auth='.$session['token'];

        try {
            $response = $this->sendUpstreamRequest($request, $target, $headers);
        } catch (ConnectionException $e) {
            Log::warning('FileBrowser proxy connection failed', [
                'site' => $site->uuid,
                'upstream' => $upstream,
                'error' => $e->getMessage(),
            ]);
            Cache::forget('filebrowser_upstream:'.$site->uuid);
            $this->forgetSession($site, $userId);

            return $this->errorResponse('انتهت مهلة الاتصال بـ FileBrowser. أعد تحميل الصفحة.', 504);
        }

        if (in_array($response->status(), [401, 403], true)) {
            $this->forgetSession($site, $userId);
            $this->credentials->ensureCredentials($site, force: true);
            $session = $this->getOrCreateSession($site->fresh(), $userId, $upstream);
            if ($session === null) {
                return $this->errorResponse('فشل إعادة المصادقة مع FileBrowser', 502);
            }
            $headers['Authorization'] = 'Bearer '.$session['token'];
            $headers['Cookie'] = 'auth='.$session['token'];
            $response = $this->sendUpstreamRequest($request, $target, $headers);
        }

        if ($response->failed() && $this->looksLikeStartupLog($response->body())) {
            return $this->errorResponse(
                'FileBrowser لا يزال يبدأ أو قاعدة البيانات غير جاهزة. انتظر دقيقة ثم أعد التحميل.',
                503
            );
        }

        return $this->buildProxiedResponse($request, $response, $upstream, $proxyBase, $site);
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function sendUpstreamRequest(Request $request, string $target, array $headers): \Illuminate\Http\Client\Response
    {
        $pending = Http::withHeaders($headers)
            ->withOptions([
                'allow_redirects' => false,
                'verify' => false,
                'connect_timeout' => 15,
            ])
            ->timeout(90);

        $method = strtoupper($request->method());
        $body = in_array($method, ['GET', 'HEAD'], true) ? null : $request->getContent();

        if ($body !== null && $body !== '') {
            return $pending->withBody($body, $request->header('Content-Type', 'application/octet-stream'))
                ->send($method, $target);
        }

        return $pending->send($method, $target);
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

        try {
            $login = Http::withOptions(['verify' => false, 'connect_timeout' => 15])
                ->timeout(45)
                ->asJson()
                ->post(rtrim($upstream, '/').'/api/login', [
                    'username' => $creds['username'],
                    'password' => $creds['password'],
                ]);
        } catch (\Throwable $e) {
            Log::warning('FileBrowser login request failed', [
                'site' => $site->uuid,
                'upstream' => $upstream,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $login->successful()) {
            Log::warning('FileBrowser login rejected', [
                'site' => $site->uuid,
                'status' => $login->status(),
                'body' => \Illuminate\Support\Str::limit($login->body(), 200),
            ]);

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
        string $proxyBase,
        CoolifyWordpressSite $site
    ): Response {
        $status = $upstreamResponse->status();
        $contentType = (string) $upstreamResponse->header('Content-Type');
        $body = $upstreamResponse->body();

        $upstreamOrigin = rtrim($upstreamOrigin, '/');
        $proxyBase = rtrim($proxyBase, '/');

        if ($this->shouldRewriteBody($contentType)) {
            $body = str_replace($upstreamOrigin, $proxyBase, $body);
            foreach ($this->upstreamResolver->publicCandidateUrls($site) as $candidate) {
                $body = str_replace(rtrim($candidate, '/'), $proxyBase, $body);
            }
            $body = preg_replace('#(https?:)?//[^/\'"]+/api/#', $proxyBase.'/api/', $body) ?? $body;
            $body = preg_replace('#(href|src)=(["\'])/(?!/)#', '$1=$2'.$proxyBase.'/', $body) ?? $body;
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
                    foreach ($this->upstreamResolver->publicCandidateUrls($site) as $candidate) {
                        $value = str_replace(rtrim($candidate, '/'), $proxyBase, $value);
                    }
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

    protected function looksLikeStartupLog(string $body): bool
    {
        return str_contains($body, 'Using config file:')
            || str_contains($body, 'Using database:')
            || str_contains($body, 'Error: timeout');
    }

    protected function errorResponse(string $message, int $status): Response
    {
        $html = '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="utf-8"><title>FileBrowser</title>'
            .'<style>body{font-family:sans-serif;background:#1a1a1a;color:#eee;padding:2rem;line-height:1.6}'
            .'.box{max-width:520px;margin:auto;background:#2a2a2a;padding:1.5rem;border-radius:8px}</style></head><body>'
            .'<div class="box"><h2>FileBrowser</h2><p>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p></div></body></html>';

        return response($html, $status)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
