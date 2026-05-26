<?php

namespace App\Console\Commands;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\WordpressSiteProvisioningService;
use Illuminate\Console\Command;

class AttachFilebrowserToWordpressSiteCommand extends Command
{
    protected $signature = 'wordpress-sites:attach-filebrowser {uuid : Site UUID}';

    protected $description = 'إرفاق FileBrowser بموقع WordPress موجود على Coolify';

    public function handle(WordpressSiteProvisioningService $provisioning): int
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $this->argument('uuid'))->first();

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
