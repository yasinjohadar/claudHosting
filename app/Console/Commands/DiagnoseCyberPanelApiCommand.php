<?php

namespace App\Console\Commands;

use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelSettingsService;
use Illuminate\Console\Command;

class DiagnoseCyberPanelApiCommand extends Command
{
    protected $signature = 'cyberpanel:diagnose-api {--clear-cache : مسح كاش الباقات}';

    protected $description = 'فحص اتصال CyberPanel API';

    public function handle(CyberPanelApiService $api, CyberPanelSettingsService $settings): int
    {
        if ($this->option('clear-cache')) {
            $api->clearPackagesCache();
            $settings->clearCache();
            $this->info('تم مسح الكاش');
        }

        $config = $settings->getConnectionConfig();
        $this->info('Host: '.($config['host'] ?: '(فارغ)'));
        $this->line('Port: '.($config['port'] ?? 8090));
        $this->line('API style: '.($config['api_style'] ?? 'cloud'));
        $this->line('Base URL: '.$api->getBaseUrl());
        $this->newLine();

        if (! $api->isConfigured()) {
            $this->error('CyberPanel غير مضبوط — احفظ الإعدادات من /admin/cyberpanel/settings');

            return self::FAILURE;
        }

        foreach ([
            'verifyConn' => fn () => $api->verifyConnection(),
            'cloudAuth' => fn () => ['success' => $api->discoverAuthorizationHeader() !== null, 'message' => $api->discoverAuthorizationHeader() ? 'تم اشتقاق/اكتشاف التوكن' : 'فشل اكتشاف التوكن'],
            'fetchPackages' => fn () => $api->listPackages(),
            'fetchWebsites' => fn () => $api->listWebsites(),
        ] as $label => $fn) {
            $res = $fn();
            $this->printResult($label, $res, match ($label) {
                'fetchPackages' => count($api->normalizeList($res['packages'] ?? [])),
                'fetchWebsites' => count($api->normalizeList($res['websites'] ?? [])),
                default => null,
            });
        }

        $this->newLine();
        $this->info('✓ انتهى الفحص');

        return self::SUCCESS;
    }

    /**
     * @param  array{success?: bool, message?: string, status?: int}  $res
     */
    protected function printResult(string $label, array $res, ?int $count = null): void
    {
        $ok = $res['success'] ?? false;
        $status = $res['status'] ?? '—';
        $suffix = $count !== null ? " → {$count} عنصر" : '';
        if ($ok) {
            $this->line("  <fg=green>✓</> {$label} (HTTP {$status}){$suffix}");
        } else {
            $msg = $res['message'] ?? 'فشل';
            $this->line("  <fg=red>✗</> {$label} (HTTP {$status}): {$msg}");
        }
    }
}
