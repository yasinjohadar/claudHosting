<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use App\Events\WhatsAppMessageReceived;
use App\Listeners\AutoReplyWhatsAppListener;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists($helper = app_path('Helpers/StorageHelper.php'))) {
            require_once $helper;
        }
        // Register console commands (if any)
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CheckWhmcsConfig::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // تسجيل PermissionServiceProvider
        $this->app->register(PermissionServiceProvider::class);

        Event::listen(WhatsAppMessageReceived::class, AutoReplyWhatsAppListener::class);

        View::composer([
            'frontend.layouts.header',
            'frontend.layouts.footer',
            'frontend.pages.contact',
        ], function ($view) {
            $view->with('settings', Setting::getAllKeyValue());
        });
    }
}
