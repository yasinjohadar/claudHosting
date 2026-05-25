<?php

namespace App\Console\Commands;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\WordpressCloudflareService;
use App\Services\Coolify\WordpressSiteProvisioningService;
use Illuminate\Console\Command;

class SyncWordpressSiteCommand extends Command
{
    protected $signature = 'coolify:sync-wordpress-site
                            {slug : معرّف الموقع (مثل site1)}
                            {--cloudflare : مزامنة metadata من سجل DNS على Cloudflare}
                            {--domain : تطبيق النطاق المخصص على Coolify (Traefik)}
                            {--coolify : سحب حالة الخدمة من Coolify API}
                            {--all : تنفيذ الثلاثة معاً}';

    protected $description = 'مزامنة موقع WordPress مع Cloudflare و/أو Coolify (للاستخدام على السيرفر عبر SSH)';

    public function handle(
        WordpressCloudflareService $cloudflare,
        WordpressSiteProvisioningService $provisioning
    ): int {
        $slug = strtolower(trim((string) $this->argument('slug')));
        $site = CoolifyWordpressSite::query()->where('slug', $slug)->first();

        if ($site === null) {
            $this->error('لم يُعثر على موقع بالمعرّف: '.$slug);

            return self::FAILURE;
        }

        $runCloudflare = $this->option('all') || $this->option('cloudflare');
        $runDomain = $this->option('all') || $this->option('domain');
        $runCoolify = $this->option('all') || $this->option('coolify');

        if (! $runCloudflare && ! $runDomain && ! $runCoolify) {
            $this->warn('حدّد على الأقل: --cloudflare أو --domain أو --coolify أو --all');
            $this->line('مثال: php artisan coolify:sync-wordpress-site site1 --all');

            return self::FAILURE;
        }

        $this->info('الموقع: '.$site->display_name.' ('.$site->slug.') — UUID: '.$site->uuid);
        $this->line('public_url: '.($site->public_url ?: '—'));
        $this->line('service_uuid: '.($site->service_uuid ?: '—'));
        $this->newLine();

        $failed = false;

        if ($runCloudflare) {
            $this->info('→ مزامنة Cloudflare...');
            $result = $cloudflare->syncFromExistingDns($site->fresh());
            if ($result['ok'] ?? false) {
                $meta = $result['metadata'] ?? [];
                $this->line('  ✓ '.$meta['fqdn'].' → '.$meta['origin'].' (proxied='.json_encode($meta['proxied'] ?? false).')');
            } else {
                $this->error('  ✗ '.($result['message'] ?? 'فشل'));
                $failed = true;
            }
        }

        if ($runDomain) {
            $this->info('→ تطبيق النطاق على Coolify...');
            $result = $provisioning->applyCoolifyDomain($site->fresh());
            if ($result['ok'] ?? false) {
                $this->line('  ✓ تم التعيين وإعادة التشغيل — انتظر دقيقة ثم جرّب الرابط');
            } else {
                $this->error('  ✗ '.($result['message'] ?? 'فشل'));
                $failed = true;
            }
        }

        if ($runCoolify) {
            $this->info('→ سحب حالة الخدمة من Coolify API...');
            try {
                $provisioning->syncSiteFromCoolify($site->fresh());
                $site = $site->fresh();
                $this->line('  ✓ الحالة: '.$site->status);
                $urls = $site->metadata['coolify_urls'] ?? $site->metadata['coolify_default_url'] ?? null;
                if ($urls) {
                    $this->line('  روابط: '.(is_array($urls) ? json_encode($urls, JSON_UNESCAPED_UNICODE) : $urls));
                }
            } catch (\Throwable $e) {
                $this->error('  ✗ '.$e->getMessage());
                $failed = true;
            }
        }

        $site = $site->fresh();
        $cf = $site->metadata['cloudflare'] ?? null;
        if (is_array($cf) && $cf !== []) {
            $this->newLine();
            $this->line('metadata.cloudflare: '.json_encode($cf, JSON_UNESCAPED_UNICODE));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
