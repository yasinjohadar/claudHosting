<?php

namespace App\Console\Commands;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\WordpressSiteProvisioningService;
use Illuminate\Console\Command;

class AttachFilebrowserToWordpressSiteCommand extends Command
{
    protected $signature = 'wordpress-sites:attach-filebrowser
                            {uuid? : Site UUID}
                            {--slug= : Site slug (مثل site5) بدل UUID}';

    protected $description = 'إرفاق FileBrowser بموقع WordPress موجود على Coolify';

    public function handle(WordpressSiteProvisioningService $provisioning): int
    {
        $uuid = $this->argument('uuid');
        $slug = $this->option('slug');

        if (! $uuid && ! $slug) {
            $this->error('حدّد uuid أو --slug=site5');

            return self::FAILURE;
        }

        $site = CoolifyWordpressSite::query()
            ->when($uuid, fn ($q) => $q->where('uuid', $uuid))
            ->when(! $uuid && $slug, fn ($q) => $q->where('slug', $slug))
            ->first();

        if (! $site) {
            $this->error('الموقع غير موجود.');

            return self::FAILURE;
        }

        $result = $provisioning->attachFilebrowser($site);

        if ($result['ok'] ?? false) {
            $this->info($result['message'] ?? 'تم بنجاح');

            return self::SUCCESS;
        }

        $this->error($result['message'] ?? 'فشل الإرفاق');

        return self::FAILURE;
    }
}
