<?php

namespace App\Providers;

use App\Events\PaymentReceived;
use App\Events\WhatsAppMessageReceived;
use App\Listeners\AutoReplyWhatsAppListener;
use App\Listeners\SendPaymentEmailListener;
use App\Listeners\SendPaymentWhatsappListener;
use App\Models\Setting;
use App\Services\Mail\MailTemplateResolver;
use App\Services\Mail\TemplateRendererService;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\View\Composers\SeoComposer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        if (file_exists($helper = app_path('helpers.php'))) {
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
        app(MailTemplateResolver::class)->ensureDefaults();

        try {
            app(WhatsAppSettingsService::class)->initializeDefaults();
        } catch (\Throwable) {
            // DB may not be ready during install/migrate
        }

        // تسجيل PermissionServiceProvider
        $this->app->register(PermissionServiceProvider::class);

        Event::listen(WhatsAppMessageReceived::class, AutoReplyWhatsAppListener::class);
        Event::listen(PaymentReceived::class, SendPaymentEmailListener::class);
        Event::listen(PaymentReceived::class, SendPaymentWhatsappListener::class);

        View::composer([
            'frontend.layouts.header',
            'frontend.layouts.footer',
            'frontend.pages.contact',
        ], function ($view) {
            $view->with('settings', Setting::getAllKeyValue());
        });

        View::composer('frontend.layouts.master', SeoComposer::class);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $resolver = app(MailTemplateResolver::class);
            $renderer = app(TemplateRendererService::class);
            $template = $resolver->resolve('auth.verify_email');

            $context = [
                'user_name' => $notifiable->name ?? 'User',
                'email' => $notifiable->email ?? '',
                'action_url' => $url,
            ];

            return (new MailMessage)
                ->subject($renderer->render($template['subject'], $context))
                ->line(strip_tags($renderer->render($template['body_html'], $context)))
                ->action('تأكيد البريد الإلكتروني', $url);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $resolver = app(MailTemplateResolver::class);
            $renderer = app(TemplateRendererService::class);
            $template = $resolver->resolve('auth.reset_password');

            $context = [
                'user_name' => $notifiable->name ?? 'User',
                'email' => $notifiable->email ?? '',
                'action_url' => $url,
                'expire_minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ];

            return (new MailMessage)
                ->subject($renderer->render($template['subject'], $context))
                ->line(strip_tags($renderer->render($template['body_html'], $context)))
                ->action('إعادة تعيين كلمة المرور', $url);
        });
    }
}
