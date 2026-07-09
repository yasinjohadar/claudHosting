<?php

namespace App\Providers;

use App\Models\EmailSetting;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            $activeSettings = EmailSetting::getActive();

            if ($activeSettings) {
                $activeSettings->applyToConfig();
            }
        } catch (\Exception $e) {
            //
        }
    }
}
