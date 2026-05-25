<?php

namespace App\Services\Coolify;

use App\Models\CoolifyActivityLog;
use Illuminate\Support\Facades\Mail;

class CoolifyOperationsNotificationService
{
    public function __construct(
        protected CoolifyOperationsService $operations,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @return array{sent: int, issues: array<int, string>}
     */
    public function checkAndNotify(): array
    {
        if (! config('coolify.ops_notifications.enabled', true)) {
            return ['sent' => 0, 'issues' => []];
        }

        $ops = $this->operations->build();
        $issues = [];

        if (! ($ops['connected'] ?? false)) {
            $issues[] = 'Coolify API غير متصل';
        }

        foreach ($ops['failed_deployments'] ?? [] as $d) {
            $issues[] = 'نشر فاشل: '.($d['application_name'] ?? $d['uuid'] ?? '—');
        }

        foreach ($ops['unhealthy_resources'] ?? [] as $r) {
            $issues[] = 'مورد غير سليم: '.($r['type_label'] ?? '').' — '.($r['name'] ?? '');
        }

        foreach ($ops['failed_snapshots'] ?? [] as $s) {
            $issues[] = 'لقطة فاشلة: '.($s['name'] ?? $s['uuid'] ?? '');
        }

        foreach ($ops['wordpress_issues'] ?? [] as $w) {
            if (($w['status'] ?? '') === 'failed') {
                $issues[] = 'WordPress فاشل: '.($w['name'] ?? '');
            }
        }

        $issues = array_values(array_unique($issues));
        if ($issues === []) {
            return ['sent' => 0, 'issues' => []];
        }

        $this->logIssues($issues);
        $sent = $this->notifyAdmins($issues);

        return ['sent' => $sent, 'issues' => $issues];
    }

    /**
     * @param  array<int, string>  $issues
     */
    protected function logIssues(array $issues): void
    {
        try {
            CoolifyActivityLog::create([
                'action' => 'ops_alert',
                'resource_type' => 'operations',
                'resource_name' => 'مركز العمليات',
                'message' => implode(' | ', array_slice($issues, 0, 5)),
            ]);
        } catch (\Throwable) {
            // ignore
        }
    }

    /**
     * @param  array<int, string>  $issues
     */
    protected function notifyAdmins(array $issues): int
    {
        $emails = array_filter(array_map('trim', explode(',', (string) config('coolify.ops_notifications.emails', ''))));
        if ($emails === []) {
            return 0;
        }

        $body = "تنبيهات Coolify:\n\n".implode("\n", array_map(fn ($i) => '• '.$i, $issues));
        $sent = 0;

        foreach ($emails as $email) {
            try {
                Mail::raw($body, function ($message) use ($email) {
                    $message->to($email)->subject('تنبيه Coolify — مركز العمليات');
                });
                $sent++;
            } catch (\Throwable) {
                // skip failed mail
            }
        }

        return $sent;
    }
}
