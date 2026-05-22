<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;

class WordpressMcpBootstrapService
{
    public function __construct(
        protected WordpressCliService $cli,
        protected WordpressContainerResolver $resolver
    ) {}

    /**
     * تركيب MCP Server (إضافة) + تحديث WP-CLI + حزمة ai-command + Application Password + wp mcp server add.
     *
     * @return array{success: bool, message: string, output: string, data?: array<string, mixed>}
     */
    public function bootstrap(CoolifyWordpressSite $site): array
    {
        $resolved = $this->resolver->resolve($site, true);
        if (! ($resolved['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $resolved['message'] ?? 'تعذّر تحديد الحاوية',
                'output' => $this->resolver->discoveryDebugReport($site),
            ];
        }

        $siteUrl = $this->siteBaseUrl($site);
        if ($siteUrl === '') {
            return [
                'success' => false,
                'message' => 'لا يوجد رابط عام للموقع (public_url).',
                'output' => '',
            ];
        }

        $lines = [];
        $pluginUrl = config('wordpress_mcp.mcp_server_plugin_url');
        $aiPackage = config('wordpress_mcp.wp_cli_ai_package');
        $userId = (int) config('wordpress_mcp.application_password_user_id', 1);
        $passLabel = (string) config('wordpress_mcp.application_password_label', 'ClaudHosting MCP');
        $alias = (string) config('wordpress_mcp.mcp_server_alias', 'claudhosting');

        $lines[] = '=== تحديث WP-CLI ===';
        $cliUpdate = $this->cli->run($site, 'cli update --yes', 180);
        $lines[] = $this->formatStep('cli update', $cliUpdate);

        $lines[] = '';
        $lines[] = '=== إضافة MCP Server (WordPress plugin) ===';
        $plugin = $this->cli->run(
            $site,
            'plugin install --activate '.escapeshellarg($pluginUrl),
            300
        );
        $lines[] = $this->formatStep('mcp-server plugin', $plugin);

        $lines[] = '';
        $lines[] = '=== حزمة WP-CLI AI (mcp-wp) ===';
        $package = $this->cli->run($site, 'package install '.escapeshellarg($aiPackage), 300);
        $lines[] = $this->formatStep('package install', $package);
        if (! ($package['success'] ?? false)) {
            $lines[] = '(اختياري) إن فشلت الحزمة، يكفي إضافة MCP Server + Application Password لـ Cursor.';
        }

        $lines[] = '';
        $lines[] = '=== Application Password ===';
        $revoke = $this->cli->run(
            $site,
            'user application-password list '.$userId.' --format=json',
            60
        );
        $existing = $this->parseJsonList($revoke['output'] ?? '');
        foreach ($existing as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = (string) ($row['name'] ?? $row['application'] ?? '');
            if (strcasecmp($name, $passLabel) === 0 && ! empty($row['uuid'])) {
                $this->cli->run($site, 'user application-password delete '.$userId.' '.escapeshellarg((string) $row['uuid']), 60);
            }
        }

        $appPass = $this->cli->run(
            $site,
            'user application-password create '.$userId.' '.escapeshellarg($passLabel).' --porcelain',
            90
        );
        $password = trim($appPass['output'] ?? '');
        $lines[] = $this->formatStep('application-password', $appPass);

        if ($password === '' || ! ($appPass['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'فشل إنشاء Application Password',
                'output' => implode("\n", $lines),
            ];
        }

        $userLogin = $this->resolveUserLogin($site, $userId) ?: 'admin';
        $mcpEndpoint = $this->mcpUrlWithCredentials($siteUrl, $userLogin, $password);

        $lines[] = '';
        $lines[] = '=== تسجيل MCP في WP-CLI ===';
        $mcpAdd = $this->cli->run(
            $site,
            'mcp server add '.escapeshellarg($alias).' '.escapeshellarg($mcpEndpoint),
            90
        );
        $lines[] = $this->formatStep('mcp server add', $mcpAdd);

        $verify = $this->cli->run($site, 'mcp server list', 60);
        $lines[] = $this->formatStep('mcp server list', $verify);

        $cursorSnippet = $this->cursorMcpSnippet($siteUrl, $userLogin, $password);
        $metadata = $site->metadata ?? [];
        $site->update([
            'metadata' => array_merge($metadata, [
                'wp_mcp_bootstrapped_at' => now()->toIso8601String(),
                'wp_mcp_endpoint' => preg_replace('/:([^@]+)@/', ':***@', $mcpEndpoint),
                'wp_mcp_user' => $userLogin,
                'wp_mcp_app_password' => $password,
                'wp_mcp_cursor_snippet' => $cursorSnippet,
            ]),
        ]);

        $pluginOk = $plugin['success'] ?? false;
        $overall = $pluginOk;

        return [
            'success' => $overall,
            'message' => $overall
                ? 'تم تركيب MCP + WP-CLI. انسخ إعداد Cursor من التقرير أدناه.'
                : 'اكتمل جزئياً — راجع المخرجات',
            'output' => implode("\n", $lines)."\n\n=== Cursor MCP (JSON) ===\n".$cursorSnippet,
            'data' => [
                'mcp_endpoint' => $metadata['wp_mcp_endpoint'] ?? null,
                'user' => $userLogin,
                'cursor_mcp' => json_decode($cursorSnippet, true),
            ],
        ];
    }

    protected function siteBaseUrl(CoolifyWordpressSite $site): string
    {
        foreach ([$site->public_url, $site->admin_url] as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }
            $parsed = parse_url($url);
            if (! empty($parsed['host'])) {
                $scheme = $parsed['scheme'] ?? 'https';

                return rtrim($scheme.'://'.$parsed['host'], '/');
            }
        }

        $fqdn = trim((string) (($site->metadata ?? [])['cloudflare']['fqdn'] ?? ''));
        if ($fqdn !== '') {
            return 'https://'.$fqdn;
        }

        return '';
    }

    protected function mcpUrlWithCredentials(string $siteUrl, string $userLogin, string $password): string
    {
        $password = str_replace(' ', '', $password);
        $parsed = parse_url(rtrim($siteUrl, '/'));
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        return $scheme.'://'.rawurlencode($userLogin).':'.rawurlencode($password).'@'.$host.'/wp-json/mcp/v1/mcp';
    }

    protected function resolveUserLogin(CoolifyWordpressSite $site, int $userId): string
    {
        $result = $this->cli->run($site, 'user get '.$userId.' --field=user_login', 60);
        $login = trim($result['output'] ?? '');

        return $login !== '' ? $login : 'admin';
    }

    protected function cursorMcpSnippet(string $siteUrl, string $userLogin, string $password): string
    {
        $password = str_replace(' ', '', $password);
        $template = config('wordpress_mcp.cursor_mcp_template.wordpress-remote', []);
        $template['env']['WP_API_URL'] = rtrim($siteUrl, '/').'/wp-json';
        $template['env']['WP_USERNAME'] = $userLogin;
        $template['env']['WP_APP_PASSWORD'] = $password;

        return json_encode(['mcpServers' => ['wordpress' => $template]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array{success?: bool, output?: string, exit_code?: int}  $result
     */
    protected function formatStep(string $label, array $result): string
    {
        $ok = ($result['success'] ?? false) ? 'OK' : 'FAIL';
        $out = trim($result['output'] ?? '');

        return "[{$label}] {$ok}".($out !== '' ? "\n".$out : '');
    }

    /**
     * @return array<int, mixed>
     */
    protected function parseJsonList(string $output): array
    {
        $output = trim($output);
        if ($output === '') {
            return [];
        }
        $decoded = json_decode($output, true);

        return is_array($decoded) ? $decoded : [];
    }
}
