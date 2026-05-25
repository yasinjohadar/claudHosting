<?php

namespace App\Console\Commands;

use App\Services\CoolifyApiService;
use Illuminate\Console\Command;

class DiagnoseCoolifyApiCommand extends Command
{
    protected $signature = 'coolify:diagnose-api {--clear-cache : مسح كاش إحصائيات اللوحة}';

    protected $description = 'فحص اتصال Coolify API وسبب ظهور الموارد بصفر على اللوحة';

    public function handle(CoolifyApiService $coolify): int
    {
        if ($this->option('clear-cache')) {
            $coolify->clearDashboardCache();
            $this->info('تم مسح كاش coolify_dashboard_stats');
        }

        $config = app(\App\Services\Coolify\CoolifySettingsService::class)->getConnectionConfig();
        $this->info('API URL: '.($config['api_url'] ?: '(فارغ)'));
        $this->line('Token: '.(($config['token_configured'] ?? false) ? 'مُعدّ' : 'غير مُعدّ'));
        $this->line('Base (داخلي): '.$coolify->getBaseUrl());
        $this->newLine();

        if (! $coolify->isConfigured()) {
            $this->error('Coolify غير مضبوط — احفظ URL والتوكن من /admin/coolify/settings');

            return self::FAILURE;
        }

        $this->info('اختبارات أساسية:');
        foreach ([
            'health' => fn () => $coolify->getHealth(),
            'version' => fn () => $coolify->getVersion(),
        ] as $label => $fn) {
            $res = $fn();
            $this->printResult($label, $res);
        }

        $this->newLine();
        $this->info('قوائم الموارد (ما تعتمد عليه لوحة Coolify):');

        $endpoints = [
            'servers' => fn () => $coolify->listServers(),
            'projects' => fn () => $coolify->listProjects(),
            'applications' => fn () => $coolify->listApplications(),
            'databases' => fn () => $coolify->listDatabases(),
            'services' => fn () => $coolify->listServices(),
            'deployments' => fn () => $coolify->listDeployments(),
        ];

        $anyListOk = false;
        foreach ($endpoints as $label => $fn) {
            $res = $fn();
            $count = ($res['success'] ?? false)
                ? count($coolify->normalizeList($res['data'] ?? []))
                : 0;
            if ($res['success'] ?? false) {
                $anyListOk = true;
            }
            $this->printResult($label, $res, $count);
        }

        $this->newLine();
        if (! $anyListOk) {
            $this->warn('الاتصال «متصل» لكن كل القوائم فشلت — الأرقام في اللوحة ستبقى 0.');
            $this->line('الحلول الشائعة على Coolify:');
            $this->line('  1) Keys & Tokens → API Token → Allowed IPs: اتركه فارغاً أو 0.0.0.0 أو أضف IP سيرفر الاستضافة (خروج PHP).');
            $this->line('  2) أنشئ التوكن بصلاحية root/* على نفس الفريق الذي يملك السيرفرات.');
            $this->line('  3) تأكد أن API URL يشير لوحة Coolify (مثل https://coolify.example.com) وليس موقع Laravel فقط.');
            $this->line('  4) راجع storage/logs/laravel.log بعد التحديث — سجلات Coolify API error.');

            return self::FAILURE;
        }

        $this->info('✓ القوائم تعمل — اضغط «تحديث» في لوحة Coolify أو: php artisan coolify:diagnose-api --clear-cache');

        return self::SUCCESS;
    }

    /**
     * @param  array{success?: bool, message?: string, status?: int, data?: mixed}  $res
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
