<?php

namespace App\Console\Commands;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\FilebrowserCredentialService;
use Illuminate\Console\Command;

class SyncFilebrowserCredentialsCommand extends Command
{
    protected $signature = 'wordpress-sites:sync-filebrowser-credentials
                            {uuid? : UUID الموقع}
                            {--slug= : slug الموقع بدلاً من UUID}
                            {--force : إعادة تعيين كلمة المرور حتى إن وُجدت بيانات مخزّنة}';

    protected $description = 'مزامنة بيانات دخول FileBrowser لموقع WordPress (SSH + metadata مشفّر)';

    public function handle(FilebrowserCredentialService $credentials): int
    {
        $site = $this->resolveSite();
        if ($site === null) {
            $this->error('لم يُعثر على الموقع. حدّد uuid أو --slug=');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $this->info('مزامنة FileBrowser: '.$site->display_name.' ('.$site->uuid.')');

        $result = $force
            ? $credentials->rotate($site)
            : $credentials->ensureCredentials($site);

        if ($result['ok'] ?? false) {
            $this->info($force ? 'تم إعادة تعيين بيانات الدخول.' : 'بيانات الدخول جاهزة في metadata.');

            return self::SUCCESS;
        }

        $this->error($result['message'] ?? 'فشلت المزامنة');

        return self::FAILURE;
    }

    protected function resolveSite(): ?CoolifyWordpressSite
    {
        $uuid = trim((string) $this->argument('uuid'));
        if ($uuid !== '') {
            return CoolifyWordpressSite::query()->where('uuid', $uuid)->first();
        }

        $slug = strtolower(trim((string) $this->option('slug')));
        if ($slug !== '') {
            return CoolifyWordpressSite::query()->where('slug', $slug)->first();
        }

        return null;
    }
}
