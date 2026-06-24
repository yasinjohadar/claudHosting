<?php

use App\Http\Controllers\Admin\AIBlogPostController;
use App\Http\Controllers\Admin\AIContentController;
use App\Http\Controllers\Admin\AIModelController;
use App\Http\Controllers\Admin\AISettingsController;
use App\Http\Controllers\Admin\AppStorageAnalyticsController;
use App\Http\Controllers\Admin\AppStorageController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BackupScheduleController;
use App\Http\Controllers\Admin\BackupStorageAnalyticsController;
use App\Http\Controllers\Admin\BackupStorageController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\Cloudflare\CloudflareRegistrarController;
use App\Http\Controllers\Admin\Cloudflare\CloudflareSettingsController;
use App\Http\Controllers\Admin\Cloudflare\CloudflareZoneController;
use App\Http\Controllers\Admin\Coolify\CoolifyApplicationController;
use App\Http\Controllers\Admin\Coolify\CoolifyBackupController;
use App\Http\Controllers\Admin\Coolify\CoolifyCatalogController;
use App\Http\Controllers\Admin\Coolify\CoolifyCatalogSettingsController;
use App\Http\Controllers\Admin\Coolify\CoolifyCloudTokenController;
use App\Http\Controllers\Admin\Coolify\CoolifyDatabaseController;
use App\Http\Controllers\Admin\Coolify\CoolifyDeploymentController;
use App\Http\Controllers\Admin\Coolify\CoolifyGithubAppController;
use App\Http\Controllers\Admin\Coolify\CoolifyHetznerController;
use App\Http\Controllers\Admin\Coolify\CoolifyMetricsController;
use App\Http\Controllers\Admin\Coolify\CoolifyOperationsController;
use App\Http\Controllers\Admin\Coolify\CoolifyPrivateKeyController;
use App\Http\Controllers\Admin\Coolify\CoolifyProjectController;
use App\Http\Controllers\Admin\Coolify\CoolifyProjectSnapshotController;
use App\Http\Controllers\Admin\Coolify\CoolifyResourceSnapshotController;
use App\Http\Controllers\Admin\Coolify\CoolifyResourceController;
use App\Http\Controllers\Admin\Coolify\CoolifyServerController;
use App\Http\Controllers\Admin\Coolify\CoolifyServiceController;
use App\Http\Controllers\Admin\Coolify\CoolifySettingsController;
use App\Http\Controllers\Admin\Coolify\CoolifySnapshotScheduleController;
use App\Http\Controllers\Admin\Coolify\CoolifySystemController;
use App\Http\Controllers\Admin\Coolify\CoolifyTeamController;
use App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteController;
use App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteFilesController;
use App\Http\Controllers\Admin\CustomerServiceController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Domain\DomainHubController;
use App\Http\Controllers\Admin\Domain\DomainSearchController;
use App\Http\Controllers\Admin\Domain\DomainSettingsController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\MailTemplateController;
use App\Http\Controllers\Admin\Namecom\NamecomDomainController;
use App\Http\Controllers\Admin\Namecom\NamecomSettingsController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PackageOrderRequestController;
use App\Http\Controllers\Admin\OfferedServiceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StorageDiskMappingController;
use App\Http\Controllers\Admin\SystemDatabaseController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsAppMessageController;
use App\Http\Controllers\Admin\WhatsAppSettingsController;
use App\Http\Controllers\Admin\WhatsAppWebController;
use App\Http\Controllers\Admin\WhatsAppWebSettingsController;
use App\Http\Controllers\Admin\Whm\WhmAccountController;
use App\Http\Controllers\Admin\Whm\WhmServerStatusController;
use App\Http\Controllers\Admin\Whm\WhmSettingsController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/robots.txt', \App\Http\Controllers\Frontend\RobotsController::class);

// الصفحة الرئيسية — عرض الفرونت اند
Route::get('/', function () {
    $latestBlogPosts = \App\Models\BlogPost::published()
        ->with(['category', 'tags'])
        ->latest('published_at')
        ->take(6)
        ->get();
    $featuredPackages = \App\Models\Product::where('hidden', false)
        ->where('status', 'Active')
        ->orderBy('gid')
        ->orderBy('name')
        ->take(6)
        ->get();
    $hero = app(\App\Services\HeroSettingsService::class)->resolveForFrontend();

    return view('frontend.pages.index', compact('latestBlogPosts', 'featuredPackages', 'hero'));
})->name('home');

// مسارات المصادقة
Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');

// WhatsApp Webhook (public - no auth) for Meta to verify and send events
Route::get('/api/webhooks/whatsapp', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify']);
Route::post('/api/webhooks/whatsapp', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);

Route::get('/client/impersonate/{token}', [\App\Http\Controllers\Client\ClientImpersonationController::class, 'consume'])
    ->name('client.impersonate');

Route::get('/customer/impersonate/{token}', function (string $token) {
    return redirect()->route('client.impersonate', ['token' => $token]);
});

Route::redirect('/customer', '/client');
Route::redirect('/customer/{path}', '/client/{path}')->where('path', '.*');

// المسارات المحمية
Route::middleware(['auth'])->group(function () {
    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\ClientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/services', [\App\Http\Controllers\Client\ClientPortalController::class, 'services'])->name('services');
        Route::get('/invoices', [\App\Http\Controllers\Client\ClientPortalController::class, 'invoices'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Client\ClientPortalController::class, 'invoiceShow'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pay', [\App\Http\Controllers\Client\ClientPaymentController::class, 'payForm'])->name('invoices.pay');
        Route::post('/invoices/{invoice}/pay', [\App\Http\Controllers\Client\ClientPaymentController::class, 'payStore'])->name('invoices.pay.store');
        Route::get('/payments', [\App\Http\Controllers\Client\ClientPaymentController::class, 'index'])->name('payments.index');
        Route::prefix('coolify/projects')->name('coolify.projects.')->group(function () {
            Route::get('/{uuid}', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'show'])->name('show');
            Route::post('/{uuid}/applications/{appUuid}/deploy', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'deployApplication'])->name('applications.deploy');
            Route::post('/{uuid}/applications/{appUuid}/restart', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'restartApplication'])->name('applications.restart');
            Route::get('/{uuid}/applications/{appUuid}/logs', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'applicationLogs'])->name('applications.logs');
            Route::get('/{uuid}/applications/{appUuid}/deployments', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'applicationDeployments'])->name('applications.deployments');
            Route::post('/{uuid}/services/{serviceUuid}/{action}', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'serviceLifecycle'])
                ->name('services.lifecycle')
                ->where('action', 'start|stop|restart');
            Route::get('/{uuid}/services/{serviceUuid}/logs', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'serviceLogs'])->name('services.logs');
            Route::post('/{uuid}/databases/{databaseUuid}/{action}', [\App\Http\Controllers\Client\ClientCoolifyProjectController::class, 'databaseLifecycle'])
                ->name('databases.lifecycle')
                ->where('action', 'start|stop|restart');
        });
        Route::prefix('wordpress-sites')->name('wordpress-sites.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Client\ClientWordpressSiteController::class, 'index'])->name('index');
            Route::get('/{uuid}/status', [CoolifyWordpressSiteController::class, 'status'])->name('status');
            Route::get('/{uuid}/wp-info', [CoolifyWordpressSiteController::class, 'wpInfo'])->name('wp-info');
            Route::post('/{uuid}/wp-action', [CoolifyWordpressSiteController::class, 'wpAction'])->name('wp-action');
            Route::get('/{uuid}/wp-job', [CoolifyWordpressSiteController::class, 'wpJob'])->name('wp-job');
            Route::get('/{uuid}/files/list', [CoolifyWordpressSiteFilesController::class, 'list'])->name('files.list');
            Route::get('/{uuid}/files/read', [CoolifyWordpressSiteFilesController::class, 'read'])->name('files.read');
            Route::post('/{uuid}/files/write', [CoolifyWordpressSiteFilesController::class, 'write'])->name('files.write');
            Route::post('/{uuid}/files/upload', [CoolifyWordpressSiteFilesController::class, 'upload'])->name('files.upload');
            Route::post('/{uuid}/files/mkdir', [CoolifyWordpressSiteFilesController::class, 'mkdir'])->name('files.mkdir');
            Route::post('/{uuid}/files/rename', [CoolifyWordpressSiteFilesController::class, 'rename'])->name('files.rename');
            Route::delete('/{uuid}/files', [CoolifyWordpressSiteFilesController::class, 'destroy'])->name('files.destroy');
            Route::get('/{uuid}/files/download', [CoolifyWordpressSiteFilesController::class, 'download'])->name('files.download');
            Route::get('/{uuid}/docker/logs', [CoolifyWordpressSiteFilesController::class, 'dockerLogs'])->name('docker.logs');
            Route::get('/{uuid}/docker/inspect', [CoolifyWordpressSiteFilesController::class, 'dockerInspect'])->name('docker.inspect');
            Route::get('/{uuid}/docker/stats', [\App\Http\Controllers\Client\ClientWordpressSiteDockerController::class, 'stats'])->name('docker.stats');
            Route::get('/{uuid}/docker/health', [\App\Http\Controllers\Client\ClientWordpressSiteDockerController::class, 'health'])->name('docker.health');
            Route::post('/{uuid}/docker/db-backup', [\App\Http\Controllers\Client\ClientWordpressSiteDockerController::class, 'dbBackup'])->name('docker.db-backup');
            Route::post('/{uuid}/terminal/session', [CoolifyWordpressSiteFilesController::class, 'terminalSession'])->name('terminal.session');
            Route::get('/terminal/commands', [CoolifyWordpressSiteFilesController::class, 'terminalCommands'])->name('terminal.commands');
            Route::get('/{uuid}/filebrowser', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteFilebrowserController::class, 'show'])->name('filebrowser');
            Route::any('/{uuid}/filebrowser/proxy/{path?}', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteFilebrowserController::class, 'proxy'])
                ->where('path', '.*')
                ->name('filebrowser.proxy');
            Route::post('/{uuid}/sync-cloudflare', [CoolifyWordpressSiteController::class, 'syncCloudflare'])->name('sync-cloudflare');
            Route::get('/{uuid}', [\App\Http\Controllers\Client\ClientWordpressSiteController::class, 'show'])->name('show');
        });
        Route::post('/impersonate/stop', [\App\Http\Controllers\Client\ClientImpersonationController::class, 'stop'])
            ->name('impersonate.stop');
    });

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::redirect('/', '/client')->name('dashboard');
        Route::redirect('/domains', '/client')->name('domains.index');
        Route::redirect('/domains/{domain}', '/client')->where('domain', '.+')->name('domains.show');
        Route::redirect('/projects', '/client')->name('projects.index');
        Route::redirect('/projects/{uuid}', '/client/coolify/projects/{uuid}')->name('projects.show');
        Route::redirect('/hosting', '/client')->name('hosting.index');
        Route::redirect('/hosting/{account}', '/client')->name('hosting.show');
        Route::redirect('/hosting/{account}/cpanel', '/client')->name('hosting.cpanel');
    });
});

Route::middleware(['auth', 'admin.panel'])->group(function () {
    // لوحة التحكم
    Route::prefix('admin')->name('admin.')->group(function () {
        // الصفحة الرئيسية للوحة التحكم: /admin تعرض لوحة التحكم
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        // لوحة التحكم الرئيسية
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // إدارة العملاء
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/create', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');
            Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
            Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/products/{serviceId}/suspend', [CustomerController::class, 'productSuspend'])->name('productSuspend');
            Route::post('/{id}/products/{serviceId}/unsuspend', [CustomerController::class, 'productUnsuspend'])->name('productUnsuspend');
            Route::post('/{id}/products/{serviceId}/terminate', [CustomerController::class, 'productTerminate'])->name('productTerminate');
        });

        // كتالوج الخدمات (غير باقات الاستضافة)
        Route::resource('service-types', ServiceTypeController::class)->except(['show']);
        Route::resource('offered-services', OfferedServiceController::class)
            ->parameters(['offered-services' => 'service']);

        Route::resource('customer-services', CustomerServiceController::class)
            ->parameters(['customer-services' => 'customerService']);
        Route::post('customer-services/{customerService}/create-invoice', [CustomerServiceController::class, 'createInvoice'])
            ->name('customer-services.create-invoice');

        // إدارة المنتجات
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::post('/{id}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
            Route::get('/{id}', [ProductController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
        });

        // طلبات الباقات (من الفرونت اند)
        Route::prefix('order-requests')->name('order-requests.')->group(function () {
            Route::get('/', [PackageOrderRequestController::class, 'index'])->name('index');
            Route::get('/{id}', [PackageOrderRequestController::class, 'show'])->name('show');
            Route::put('/{id}', [PackageOrderRequestController::class, 'update'])->name('update');
            Route::post('/{id}/provision-whm', [PackageOrderRequestController::class, 'provisionWhm'])->name('provision-whm');
            Route::post('/{id}/provision-hosting', [PackageOrderRequestController::class, 'provisionHosting'])->name('provision-hosting');
        });

        // إعدادات الموقع (تواصل، سوشيال، عام)
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::prefix('mail-settings')->name('mail-settings.')->group(function () {
            Route::get('/', [MailSettingsController::class, 'index'])->name('index');
            Route::post('/', [MailSettingsController::class, 'update'])->name('update');
            Route::post('/test', [MailSettingsController::class, 'testConnection'])->name('test');
        });
        Route::resource('mail-templates', MailTemplateController::class)->except(['show']);

        Route::prefix('homepage')->name('homepage.')->group(function () {
            Route::get('/hero', [\App\Http\Controllers\Admin\HeroSettingsController::class, 'index'])->name('hero.index');
            Route::put('/hero', [\App\Http\Controllers\Admin\HeroSettingsController::class, 'update'])->name('hero.update');
            Route::get('/seo', [\App\Http\Controllers\Admin\PageSeoSettingsController::class, 'index'])->name('seo.index');
            Route::put('/seo', [\App\Http\Controllers\Admin\PageSeoSettingsController::class, 'update'])->name('seo.update');
            Route::post('/seo/reset', [\App\Http\Controllers\Admin\PageSeoSettingsController::class, 'reset'])->name('seo.reset');
        });

        Route::prefix('system-database')->name('system-database.')->group(function () {
            Route::get('/', [SystemDatabaseController::class, 'index'])->name('index');
            Route::post('/refresh', [SystemDatabaseController::class, 'refresh'])->name('refresh');
            Route::get('/tables/{table}', [SystemDatabaseController::class, 'table'])
                ->where('table', '[a-zA-Z0-9_]+')
                ->name('table');
        });

        // إدارة الفواتير
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
            Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [InvoiceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [InvoiceController::class, 'update'])->name('update');
            Route::delete('/{id}', [InvoiceController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('markPaid');
            Route::post('/{id}/add-payment', [InvoiceController::class, 'addPayment'])->name('addPayment');
        });

        // إدارة المدفوعات
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
            Route::post('/{payment}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
            Route::post('/{payment}/reject', [PaymentController::class, 'reject'])->name('reject');
            Route::get('/{payment}/proof', [PaymentController::class, 'proof'])->name('proof');
        });

        // إدارة التذاكر
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::get('/create', [TicketController::class, 'create'])->name('create');
            Route::post('/', [TicketController::class, 'store'])->name('store');
            Route::get('/{id}', [TicketController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [TicketController::class, 'edit'])->name('edit');
            Route::put('/{id}', [TicketController::class, 'update'])->name('update');
            Route::delete('/{id}', [TicketController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/reply', [TicketController::class, 'reply'])->name('reply');
            Route::post('/{id}/add-reply', [TicketController::class, 'reply'])->name('addReply');
            Route::post('/{id}/add-note', [TicketController::class, 'addNote'])->name('addNote');
            Route::post('/{id}/close', [TicketController::class, 'close'])->name('close');
            Route::post('/{id}/reopen', [TicketController::class, 'reopen'])->name('reopen');
        });

        // التقارير
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('/invoices', [ReportController::class, 'invoices'])->name('invoices');
            Route::get('/products', [ReportController::class, 'products'])->name('products');
            Route::get('/tickets', [ReportController::class, 'tickets'])->name('tickets');
            // Export routes
            Route::get('/export/customers', [ReportController::class, 'exportCustomers'])->name('export.customers');
            Route::get('/export/invoices', [ReportController::class, 'exportInvoices'])->name('export.invoices');
            Route::get('/export/products', [ReportController::class, 'exportProducts'])->name('export.products');
            Route::get('/export/tickets', [ReportController::class, 'exportTickets'])->name('export.tickets');
            Route::get('/products', [ReportController::class, 'products'])->name('products');
        });

        // تصدير التقارير
        Route::prefix('reports/export')->name('reports.export.')->group(function () {
            Route::get('/products', [ReportController::class, 'exportProducts'])->name('products');
        });

        // ========== Infrastructure / VPS ==========
        Route::prefix('infrastructure')->name('infrastructure.')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-connection', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureSettingsController::class, 'testConnection'])->name('settings.test-connection');
            Route::post('/settings/netcup/device-start', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureSettingsController::class, 'netcupDeviceStart'])->name('settings.netcup.device-start');
            Route::post('/settings/netcup/device-poll', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureSettingsController::class, 'netcupDevicePoll'])->name('settings.netcup.device-poll');
            Route::post('/settings/netcup/revoke', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureSettingsController::class, 'netcupRevoke'])->name('settings.netcup.revoke');

            Route::prefix('servers/{uuid}/netcup')->name('servers.netcup.')->group(function () {
                $nc = \App\Http\Controllers\Admin\Infrastructure\InfrastructureNetcupController::class;
                Route::get('/overview', [$nc, 'overview'])->name('overview');
                Route::patch('/server', [$nc, 'updateServer'])->name('server.update');
                Route::get('/snapshots', [$nc, 'snapshots'])->name('snapshots.index');
                Route::post('/snapshots', [$nc, 'storeSnapshot'])->name('snapshots.store');
                Route::delete('/snapshots/{name}', [$nc, 'destroySnapshot'])->name('snapshots.destroy');
                Route::post('/snapshots/{name}/revert', [$nc, 'revertSnapshot'])->name('snapshots.revert');
                Route::get('/disks', [$nc, 'disks'])->name('disks.index');
                Route::post('/disks/{diskName}/format', [$nc, 'formatDisk'])->name('disks.format');
                Route::get('/interfaces', [$nc, 'networkInterfaces'])->name('interfaces.index');
                Route::post('/rdns', [$nc, 'rdns'])->name('rdns');
                Route::match(['GET', 'PUT'], '/interfaces/{mac}/firewall', [$nc, 'firewall'])->name('firewall');
                Route::post('/interfaces/{mac}/firewall/reapply', [$nc, 'firewallReapply'])->name('firewall.reapply');
                Route::match(['GET', 'POST', 'DELETE'], '/iso', [$nc, 'iso'])->name('iso');
                Route::get('/isoimages', [$nc, 'isoImages'])->name('iso.images');
                Route::match(['GET', 'POST', 'DELETE'], '/rescue', [$nc, 'rescue'])->name('rescue');
                Route::get('/metrics/{type}', [$nc, 'scpMetrics'])->name('metrics');
                Route::get('/tasks', [$nc, 'taskList'])->name('tasks.index');
                Route::get('/tasks/{taskUuid}', [$nc, 'taskShow'])->name('tasks.show');
                Route::put('/tasks/{taskUuid}/cancel', [$nc, 'taskCancel'])->name('tasks.cancel');
                Route::get('/logs', [$nc, 'logs'])->name('logs');
                Route::get('/imageflavours', [$nc, 'imageFlavours'])->name('imageflavours');
                Route::post('/image', [$nc, 'setupImage'])->name('image.setup');
            });

            Route::get('/netcup/maintenance', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureNetcupController::class, 'maintenance'])->name('netcup.maintenance');
            Route::get('/netcup/ping', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureNetcupController::class, 'ping'])->name('netcup.ping');
            Route::match(['GET', 'POST'], '/netcup/ssh-keys', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureNetcupController::class, 'sshKeys'])->name('netcup.ssh-keys');
            Route::delete('/netcup/ssh-keys/{id}', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureNetcupController::class, 'deleteSshKey'])->name('netcup.ssh-keys.delete');

            Route::get('/servers', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'index'])->name('servers.index');
            Route::post('/servers/sync', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'sync'])->name('servers.sync');
            Route::get('/servers/{uuid}/edit', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'edit'])->name('servers.edit');
            Route::put('/servers/{uuid}', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'update'])->name('servers.update');
            Route::post('/servers/{uuid}/refresh', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'refresh'])->name('servers.refresh');
            Route::post('/servers/{uuid}/{action}', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'power'])
                ->name('servers.power')
                ->where('action', 'start|stop|shutdown|restart');
            Route::get('/servers/{uuid}/metrics/history', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureMetricsController::class, 'history'])->name('servers.metrics.history');
            Route::get('/servers/{uuid}/metrics', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureMetricsController::class, 'live'])->name('servers.metrics.live');
            Route::get('/servers/{uuid}/terminal/commands', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureTerminalController::class, 'commands'])->name('servers.terminal.commands');
            Route::post('/servers/{uuid}/terminal/session', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureTerminalController::class, 'session'])->name('servers.terminal.session');
            Route::get('/servers/{uuid}/terminal', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureTerminalController::class, 'show'])->name('servers.terminal');
            Route::get('/servers/{uuid}/lifecycle/images', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureLifecycleController::class, 'images'])->name('servers.lifecycle.images');
            Route::post('/servers/{uuid}/reinstall', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureLifecycleController::class, 'reinstall'])->name('servers.reinstall');
            Route::get('/servers/{uuid}', [\App\Http\Controllers\Admin\Infrastructure\InfrastructureServerController::class, 'show'])->name('servers.show');
        });

        // ========== Coolify ==========
        Route::prefix('coolify')->name('coolify.')->group(function () {
            Route::get('/', [CoolifySettingsController::class, 'overview'])->name('overview');
            Route::get('/operations', [CoolifyOperationsController::class, 'index'])->name('operations.index');
            Route::post('/operations/check-alerts', [CoolifyOperationsController::class, 'checkAlerts'])->name('operations.check-alerts');
            Route::get('/readiness', [CoolifyOperationsController::class, 'readiness'])->name('readiness.index');

            Route::prefix('metrics')->name('metrics.')->group(function () {
                Route::get('/overview', [CoolifyMetricsController::class, 'overview'])->name('overview');
                Route::get('/servers/{uuid}', [CoolifyMetricsController::class, 'server'])->name('servers');
                Route::get('/projects/{uuid}', [CoolifyMetricsController::class, 'project'])->name('projects');
                Route::get('/resources/{type}/{uuid}', [CoolifyMetricsController::class, 'resource'])->name('resources');
            });

            Route::get('/settings', [CoolifySettingsController::class, 'index'])->name('settings.index');
            Route::get('/settings/{section}', [CoolifySettingsController::class, 'section'])->name('settings.section')
                ->where('section', 'api|backups|wordpress|cloudflare|wp-cli|ssh|terminal');
            Route::put('/settings/{section}', [CoolifySettingsController::class, 'updateSection'])->name('settings.section.update')
                ->where('section', 'api|backups|wordpress|cloudflare|wp-cli|ssh|terminal');
            Route::put('/settings', [CoolifySettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-connection', [CoolifySettingsController::class, 'testConnection'])->name('settings.test');
            Route::post('/settings/test-ssh', [CoolifySettingsController::class, 'testSsh'])->name('settings.test-ssh');
            Route::post('/settings/test-terminal-bridge', [CoolifySettingsController::class, 'testTerminalBridge'])->name('settings.test-terminal-bridge');
            Route::post('/settings/discover-s3', [CoolifySettingsController::class, 'discoverS3'])->name('settings.discover-s3');

            Route::get('/system', [CoolifySystemController::class, 'index'])->name('system.index');
            Route::post('/system/enable', [CoolifySystemController::class, 'enableApi'])->name('system.enable');
            Route::post('/system/disable', [CoolifySystemController::class, 'disableApi'])->name('system.disable');

            Route::get('/resources', [CoolifyResourceController::class, 'index'])->name('resources.index');

            Route::prefix('catalog')->name('catalog.')->group(function () {
                Route::get('/', [CoolifyCatalogController::class, 'index'])->name('index');
                Route::post('/sync', [CoolifyCatalogController::class, 'sync'])->name('sync');
                Route::get('/{slug}', [CoolifyCatalogController::class, 'show'])->name('show');
                Route::get('/{slug}/install', [CoolifyCatalogController::class, 'install'])->name('install');
                Route::post('/{slug}/install', [CoolifyCatalogController::class, 'installStore'])->name('install.store');
            });

            Route::prefix('catalog-settings')->name('catalog-settings.')->group(function () {
                Route::get('/', [CoolifyCatalogSettingsController::class, 'index'])->name('index');
                Route::post('/', [CoolifyCatalogSettingsController::class, 'storeCustom'])->name('store');
                Route::put('/{id}', [CoolifyCatalogSettingsController::class, 'update'])->name('update');
                Route::delete('/{id}', [CoolifyCatalogSettingsController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('wordpress-sites')->name('wordpress-sites.')->group(function () {
                Route::get('/', [CoolifyWordpressSiteController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyWordpressSiteController::class, 'create'])->name('create');
                Route::post('/', [CoolifyWordpressSiteController::class, 'store'])->name('store');
                Route::post('/{uuid}/assign-client', [CoolifyWordpressSiteController::class, 'assignClient'])->name('assign-client');
                Route::get('/{uuid}/status', [CoolifyWordpressSiteController::class, 'status'])->name('status');
                Route::get('/{uuid}/wp-info', [CoolifyWordpressSiteController::class, 'wpInfo'])->name('wp-info');
                Route::post('/{uuid}/wp-action', [CoolifyWordpressSiteController::class, 'wpAction'])->name('wp-action');
                Route::get('/{uuid}/wp-job', [CoolifyWordpressSiteController::class, 'wpJob'])->name('wp-job');
                Route::get('/{uuid}/files/list', [CoolifyWordpressSiteFilesController::class, 'list'])->name('files.list');
                Route::get('/{uuid}/files/read', [CoolifyWordpressSiteFilesController::class, 'read'])->name('files.read');
                Route::post('/{uuid}/files/write', [CoolifyWordpressSiteFilesController::class, 'write'])->name('files.write');
                Route::post('/{uuid}/files/upload', [CoolifyWordpressSiteFilesController::class, 'upload'])->name('files.upload');
                Route::post('/{uuid}/files/mkdir', [CoolifyWordpressSiteFilesController::class, 'mkdir'])->name('files.mkdir');
                Route::post('/{uuid}/files/rename', [CoolifyWordpressSiteFilesController::class, 'rename'])->name('files.rename');
                Route::delete('/{uuid}/files', [CoolifyWordpressSiteFilesController::class, 'destroy'])->name('files.destroy');
                Route::get('/{uuid}/files/download', [CoolifyWordpressSiteFilesController::class, 'download'])->name('files.download');
                Route::get('/{uuid}/docker/logs', [CoolifyWordpressSiteFilesController::class, 'dockerLogs'])->name('docker.logs');
                Route::get('/{uuid}/docker/inspect', [CoolifyWordpressSiteFilesController::class, 'dockerInspect'])->name('docker.inspect');
                Route::get('/{uuid}/docker/stats', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteDockerController::class, 'stats'])->name('docker.stats');
                Route::get('/{uuid}/docker/health', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteDockerController::class, 'health'])->name('docker.health');
                Route::post('/{uuid}/docker/db-backup', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteDockerController::class, 'dbBackup'])->name('docker.db-backup');
                Route::post('/{uuid}/docker/db-restore', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteDockerController::class, 'dbRestore'])->name('docker.db-restore');
                Route::post('/{uuid}/terminal/session', [CoolifyWordpressSiteFilesController::class, 'terminalSession'])->name('terminal.session');
                Route::get('/terminal/commands', [CoolifyWordpressSiteFilesController::class, 'terminalCommands'])->name('terminal.commands');
                Route::get('/{uuid}/filebrowser', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteFilebrowserController::class, 'show'])->name('filebrowser');
                Route::any('/{uuid}/filebrowser/proxy/{path?}', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteFilebrowserController::class, 'proxy'])
                    ->where('path', '.*')
                    ->name('filebrowser.proxy');
                Route::post('/{uuid}/filebrowser/rotate-credentials', [\App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteFilebrowserController::class, 'rotateCredentials'])
                    ->name('filebrowser.rotate-credentials');
                Route::post('/{uuid}/sync-cloudflare', [CoolifyWordpressSiteController::class, 'syncCloudflare'])->name('sync-cloudflare');
                Route::post('/{uuid}/attach-filebrowser', [CoolifyWordpressSiteController::class, 'attachFilebrowser'])->name('attach-filebrowser');
                Route::post('/{uuid}/apply-coolify-domain', [CoolifyWordpressSiteController::class, 'applyCoolifyDomain'])->name('apply-coolify-domain');
                Route::post('/{uuid}/retry', [CoolifyWordpressSiteController::class, 'retry'])->name('retry');
                Route::post('/{uuid}/restart-coolify', [CoolifyWordpressSiteController::class, 'restartCoolify'])->name('restart-coolify');
                Route::post('/{uuid}/components/{component}/restart', [CoolifyWordpressSiteController::class, 'restartComponent'])
                    ->name('components.restart')
                    ->where('component', '[a-zA-Z0-9_-]+');
                Route::post('/{uuid}/components/{component}/redeploy', [CoolifyWordpressSiteController::class, 'redeployComponent'])
                    ->name('components.redeploy')
                    ->where('component', '[a-zA-Z0-9_-]+');
                Route::get('/{uuid}/edit', [CoolifyWordpressSiteController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyWordpressSiteController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyWordpressSiteController::class, 'destroy'])->name('destroy');
                Route::get('/{uuid}', [CoolifyWordpressSiteController::class, 'show'])->name('show');
            });

            Route::prefix('teams')->name('teams.')->group(function () {
                Route::get('/', [CoolifyTeamController::class, 'index'])->name('index');
                Route::post('/link-client', [CoolifyTeamController::class, 'linkClient'])->name('link-client');
                Route::delete('/unlink/{user}', [CoolifyTeamController::class, 'unlink'])->name('unlink');
                Route::get('/{teamId}', [CoolifyTeamController::class, 'show'])->whereNumber('teamId')->name('show');
            });

            Route::prefix('github-apps')->name('github-apps.')->group(function () {
                Route::get('/', [CoolifyGithubAppController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyGithubAppController::class, 'create'])->name('create');
                Route::post('/', [CoolifyGithubAppController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyGithubAppController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyGithubAppController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyGithubAppController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyGithubAppController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('cloud-tokens')->name('cloud-tokens.')->group(function () {
                Route::get('/', [CoolifyCloudTokenController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyCloudTokenController::class, 'create'])->name('create');
                Route::post('/', [CoolifyCloudTokenController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyCloudTokenController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyCloudTokenController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyCloudTokenController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyCloudTokenController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/validate', [CoolifyCloudTokenController::class, 'validateToken'])->name('validate');
            });

            Route::prefix('hetzner')->name('hetzner.')->group(function () {
                Route::get('/', [CoolifyHetznerController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyHetznerController::class, 'create'])->name('create');
                Route::post('/', [CoolifyHetznerController::class, 'store'])->name('store');
            });

            Route::prefix('servers')->name('servers.')->group(function () {
                Route::get('/', [CoolifyServerController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyServerController::class, 'create'])->name('create');
                Route::post('/', [CoolifyServerController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyServerController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyServerController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyServerController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyServerController::class, 'destroy'])->name('destroy');
                Route::get('/{uuid}/validate', [CoolifyServerController::class, 'validateConnection'])->name('validate');
                Route::get('/{uuid}/resources', [CoolifyServerController::class, 'resources'])->name('resources');
                Route::get('/{uuid}/domains', [CoolifyServerController::class, 'domains'])->name('domains');
            });

            Route::prefix('projects')->name('projects.')->group(function () {
                Route::get('/', [CoolifyProjectController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyProjectController::class, 'create'])->name('create');
                Route::post('/', [CoolifyProjectController::class, 'store'])->name('store');
                Route::post('/{uuid}/assign-client', [CoolifyProjectController::class, 'assignClient'])->name('assign-client');
                Route::get('/{uuid}', [CoolifyProjectController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyProjectController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyProjectController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyProjectController::class, 'destroy'])->name('destroy');
                Route::get('/{uuid}/resources', [CoolifyProjectController::class, 'resources'])->name('resources');
                Route::get('/{uuid}/environment/{environment}', [CoolifyProjectController::class, 'environment'])->name('environment');
                Route::post('/{uuid}/environments', [CoolifyProjectController::class, 'storeEnvironment'])->name('environments.store');
                Route::post('/{uuid}/snapshots/{snapshotUuid}/restore', [CoolifyProjectController::class, 'restoreSnapshot'])->name('snapshots.restore');
            });

            Route::prefix('applications')->name('applications.')->group(function () {
                Route::get('/', [CoolifyApplicationController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyApplicationController::class, 'create'])->name('create');
                Route::post('/', [CoolifyApplicationController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyApplicationController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyApplicationController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyApplicationController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyApplicationController::class, 'destroy'])->name('destroy');
                Route::get('/{uuid}/logs', [CoolifyApplicationController::class, 'logs'])->name('logs');
                Route::get('/{uuid}/logs/fetch', [CoolifyApplicationController::class, 'logsFetch'])->name('logs.fetch');
                Route::post('/{uuid}/start', [CoolifyApplicationController::class, 'start'])->name('start');
                Route::post('/{uuid}/stop', [CoolifyApplicationController::class, 'stop'])->name('stop');
                Route::post('/{uuid}/restart', [CoolifyApplicationController::class, 'restart'])->name('restart');
                Route::post('/{uuid}/deploy', [CoolifyApplicationController::class, 'deploy'])->name('deploy');
                Route::post('/{uuid}/envs', [CoolifyApplicationController::class, 'storeEnv'])->name('envs.store');
                Route::put('/{uuid}/envs/{envUuid}', [CoolifyApplicationController::class, 'updateEnv'])->name('envs.update');
                Route::delete('/{uuid}/envs/{envUuid}', [CoolifyApplicationController::class, 'destroyEnv'])->name('envs.destroy');
                Route::post('/{uuid}/envs/bulk', [CoolifyApplicationController::class, 'bulkEnvs'])->name('envs.bulk');
                Route::post('/{uuid}/storages', [CoolifyApplicationController::class, 'storeStorage'])->name('storages.store');
                Route::put('/{uuid}/storages/{storageId}', [CoolifyApplicationController::class, 'updateStorage'])->name('storages.update');
                Route::delete('/{uuid}/storages/{storageId}', [CoolifyApplicationController::class, 'destroyStorage'])->name('storages.destroy');
            });

            Route::prefix('backups')->name('backups.')->group(function () {
                Route::get('/', [CoolifyBackupController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyBackupController::class, 'create'])->name('create');
                Route::post('/', [CoolifyBackupController::class, 'store'])->name('store');
                Route::get('/databases/{databaseUuid}/configs/{configUuid}', [CoolifyBackupController::class, 'show'])->name('show');
                Route::get('/databases/{databaseUuid}/configs/{configUuid}/edit', [CoolifyBackupController::class, 'edit'])->name('edit');
                Route::put('/databases/{databaseUuid}/configs/{configUuid}', [CoolifyBackupController::class, 'update'])->name('update');
                Route::post('/databases/{databaseUuid}/configs/{configUuid}/run', [CoolifyBackupController::class, 'run'])->name('run');
                Route::delete('/databases/{databaseUuid}/configs/{configUuid}', [CoolifyBackupController::class, 'destroyConfig'])->name('destroy');
                Route::delete('/databases/{databaseUuid}/configs/{configUuid}/executions/{executionUuid}', [CoolifyBackupController::class, 'destroyExecution'])->name('executions.destroy');

                Route::prefix('projects')->name('projects.')->group(function () {
                    Route::get('/', [CoolifyProjectSnapshotController::class, 'projectsIndex'])->name('index');
                    Route::get('/wizard', [CoolifyProjectSnapshotController::class, 'wizard'])->name('wizard');
                    Route::post('/plan', [CoolifyProjectSnapshotController::class, 'plan'])->name('plan');
                    Route::post('/snapshots', [CoolifyProjectSnapshotController::class, 'store'])->name('snapshots.store');
                });

                Route::post('/resource-snapshot', [CoolifyResourceSnapshotController::class, 'store'])->name('resource-snapshot.store');

                Route::prefix('snapshots')->name('snapshots.')->group(function () {
                    Route::get('/', [CoolifyProjectSnapshotController::class, 'snapshotsIndex'])->name('index');
                    Route::get('/{uuid}/status', [CoolifyProjectSnapshotController::class, 'status'])->name('status');
                    Route::get('/{uuid}/restore-status', [CoolifyProjectSnapshotController::class, 'restoreStatus'])->name('restore-status');
                    Route::post('/{uuid}/restore', [CoolifyProjectSnapshotController::class, 'restore'])->name('restore');
                    Route::post('/{uuid}/cancel', [CoolifyProjectSnapshotController::class, 'cancel'])->name('cancel');
                    Route::post('/{uuid}/resume', [CoolifyProjectSnapshotController::class, 'resume'])->name('resume');
                    Route::post('/{uuid}/restore-drill', [CoolifyProjectSnapshotController::class, 'restoreDrill'])->name('restore-drill');
                    Route::get('/{uuid}', [CoolifyProjectSnapshotController::class, 'show'])->name('show');
                });

                Route::prefix('schedules')->name('schedules.')->group(function () {
                    Route::get('/', [CoolifySnapshotScheduleController::class, 'index'])->name('index');
                    Route::get('/create', [CoolifySnapshotScheduleController::class, 'create'])->name('create');
                    Route::post('/', [CoolifySnapshotScheduleController::class, 'store'])->name('store');
                    Route::get('/{uuid}/edit', [CoolifySnapshotScheduleController::class, 'edit'])->name('edit');
                    Route::put('/{uuid}', [CoolifySnapshotScheduleController::class, 'update'])->name('update');
                    Route::delete('/{uuid}', [CoolifySnapshotScheduleController::class, 'destroy'])->name('destroy');
                    Route::post('/{uuid}/toggle', [CoolifySnapshotScheduleController::class, 'toggle'])->name('toggle');
                    Route::post('/{uuid}/run', [CoolifySnapshotScheduleController::class, 'runNow'])->name('run');
                });
            });

            Route::prefix('databases')->name('databases.')->group(function () {
                Route::get('/', [CoolifyDatabaseController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyDatabaseController::class, 'create'])->name('create');
                Route::post('/', [CoolifyDatabaseController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyDatabaseController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyDatabaseController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyDatabaseController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyDatabaseController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/start', [CoolifyDatabaseController::class, 'start'])->name('start');
                Route::post('/{uuid}/stop', [CoolifyDatabaseController::class, 'stop'])->name('stop');
                Route::post('/{uuid}/restart', [CoolifyDatabaseController::class, 'restart'])->name('restart');
                Route::post('/{uuid}/redeploy', [CoolifyDatabaseController::class, 'redeploy'])->name('redeploy');
                Route::post('/{uuid}/reinstall', [CoolifyDatabaseController::class, 'reinstall'])->name('reinstall');
                Route::post('/{uuid}/backups', [CoolifyDatabaseController::class, 'storeBackup'])->name('backups.store');
            });

            Route::prefix('services')->name('services.')->group(function () {
                Route::get('/', [CoolifyServiceController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyServiceController::class, 'create'])->name('create');
                Route::post('/', [CoolifyServiceController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyServiceController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyServiceController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyServiceController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyServiceController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/start', [CoolifyServiceController::class, 'start'])->name('start');
                Route::post('/{uuid}/stop', [CoolifyServiceController::class, 'stop'])->name('stop');
                Route::post('/{uuid}/restart', [CoolifyServiceController::class, 'restart'])->name('restart');
                Route::post('/{uuid}/redeploy', [CoolifyServiceController::class, 'redeploy'])->name('redeploy');
                Route::get('/{uuid}/logs', [CoolifyServiceController::class, 'logs'])->name('logs');
                Route::get('/{uuid}/logs/fetch', [CoolifyServiceController::class, 'logsFetch'])->name('logs.fetch');
                Route::post('/{uuid}/envs', [CoolifyServiceController::class, 'storeEnv'])->name('envs.store');
                Route::put('/{uuid}/envs/{envUuid}', [CoolifyServiceController::class, 'updateEnv'])->name('envs.update');
                Route::delete('/{uuid}/envs/{envUuid}', [CoolifyServiceController::class, 'destroyEnv'])->name('envs.destroy');
                Route::post('/{uuid}/envs/bulk', [CoolifyServiceController::class, 'bulkEnvs'])->name('envs.bulk');
            });

            Route::prefix('deployments')->name('deployments.')->group(function () {
                Route::get('/', [CoolifyDeploymentController::class, 'index'])->name('index');
                Route::post('/deploy', [CoolifyDeploymentController::class, 'deploy'])->name('deploy');
                Route::get('/{uuid}', [CoolifyDeploymentController::class, 'show'])->name('show');
                Route::post('/{uuid}/cancel', [CoolifyDeploymentController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('private-keys')->name('private-keys.')->group(function () {
                Route::get('/', [CoolifyPrivateKeyController::class, 'index'])->name('index');
                Route::get('/create', [CoolifyPrivateKeyController::class, 'create'])->name('create');
                Route::post('/', [CoolifyPrivateKeyController::class, 'store'])->name('store');
                Route::get('/{uuid}', [CoolifyPrivateKeyController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [CoolifyPrivateKeyController::class, 'edit'])->name('edit');
                Route::put('/{uuid}', [CoolifyPrivateKeyController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [CoolifyPrivateKeyController::class, 'destroy'])->name('destroy');
            });
        });

        // ========== النطاقات ==========
        Route::prefix('domains')->name('domains.')->group(function () {
            Route::get('/settings', [DomainSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [DomainSettingsController::class, 'update'])->name('settings.update');
            Route::get('/', [DomainHubController::class, 'index'])->name('index');
            Route::post('/assign-client', [DomainHubController::class, 'assignClient'])->name('assign-client');
            Route::get('/{domain}/whm-dns', [DomainHubController::class, 'whmDns'])
                ->where('domain', '.+')
                ->name('whm-dns');
            Route::get('/search', [DomainSearchController::class, 'index'])->name('search');
        });

        Route::prefix('cloudflare')->name('cloudflare.')->group(function () {
            Route::get('/settings', [CloudflareSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [CloudflareSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-connection', [CloudflareSettingsController::class, 'testConnection'])->name('settings.test');
            Route::get('/zones', [CloudflareZoneController::class, 'index'])->name('zones.index');
            Route::get('/zones/{zoneId}', [CloudflareZoneController::class, 'show'])->name('zones.show');
            Route::get('/registrar', [CloudflareRegistrarController::class, 'index'])->name('registrar.index');
        });

        Route::prefix('namecom')->name('namecom.')->group(function () {
            Route::get('/settings', [NamecomSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [NamecomSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-connection', [NamecomSettingsController::class, 'testConnection'])->name('settings.test');
            Route::get('/domains', [NamecomDomainController::class, 'index'])->name('domains.index');
            Route::get('/domains/{domain}', [NamecomDomainController::class, 'show'])
                ->where('domain', '.+')
                ->name('domains.show');
        });

        // WHM / cPanel
        Route::prefix('whm')->name('whm.')->group(function () {
            Route::get('/settings', [WhmSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [WhmSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-connection', [WhmSettingsController::class, 'testConnection'])->name('settings.test');
            Route::get('/server', [WhmServerStatusController::class, 'index'])->name('server.index');
            Route::get('/server-status', [WhmServerStatusController::class, 'refresh'])->name('server-status');
            Route::prefix('accounts')->name('accounts.')->group(function () {
                Route::get('/', [WhmAccountController::class, 'index'])->name('index');
                Route::post('/sync', [WhmAccountController::class, 'sync'])->name('sync');
                Route::get('/create', [WhmAccountController::class, 'create'])->name('create');
                Route::post('/', [WhmAccountController::class, 'store'])->name('store');
                Route::get('/{account}', [WhmAccountController::class, 'show'])->name('show');
                Route::post('/{account}/refresh-summary', [WhmAccountController::class, 'refreshSummary'])->name('refresh-summary');
                Route::post('/{account}/change-package', [WhmAccountController::class, 'changePackage'])->name('change-package');
                Route::delete('/{account}', [WhmAccountController::class, 'destroy'])->name('destroy');
                Route::get('/{account}/cpanel', [WhmAccountController::class, 'cpanelLogin'])->name('cpanel');
                Route::post('/{account}/assign-client', [WhmAccountController::class, 'assignClient'])->name('assign-client');
                Route::post('/{account}/toggle-status', [WhmAccountController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/{account}/update-email', [WhmAccountController::class, 'updateEmail'])->name('update-email');
                Route::post('/{account}/update-password', [WhmAccountController::class, 'updatePassword'])->name('update-password');
                Route::post('/{account}/rename-user', [WhmAccountController::class, 'renameUser'])->name('rename-user');
                Route::post('/{account}/renew', [WhmAccountController::class, 'renew'])->name('renew');
            });
        });

        // ========== Blog ==========
        Route::prefix('blog')->name('blog.')->group(function () {
            Route::resource('posts', BlogPostController::class);
            Route::post('posts/{post}/toggle-featured', [BlogPostController::class, 'toggleFeatured'])->name('posts.toggle-featured');
            Route::post('posts/{post}/toggle-publish', [BlogPostController::class, 'togglePublish'])->name('posts.toggle-publish');
            Route::delete('posts/{post}/featured-image', [BlogPostController::class, 'deleteFeaturedImage'])->name('posts.delete-featured-image');
            Route::resource('categories', BlogCategoryController::class);
            Route::post('categories/{category}/toggle-active', [BlogCategoryController::class, 'toggleActive'])->name('categories.toggle-active');
            Route::resource('tags', BlogTagController::class);
            Route::get('ai-posts/create', [AIBlogPostController::class, 'create'])->name('ai-posts.create');
            Route::post('ai-posts', [AIBlogPostController::class, 'store'])->name('ai-posts.store');
            Route::post('ai-posts/generate', [AIBlogPostController::class, 'generate'])->name('ai-posts.generate');
        });

        // ========== App Storage (التخزين السحابي) ==========
        Route::prefix('storage')->name('storage.')->group(function () {
            Route::get('/', [AppStorageController::class, 'index'])->name('index');
            Route::get('/create', [AppStorageController::class, 'create'])->name('create');
            Route::post('/', [AppStorageController::class, 'store'])->name('store');
            Route::get('/{config}/edit', [AppStorageController::class, 'edit'])->name('edit');
            Route::put('/{config}', [AppStorageController::class, 'update'])->name('update');
            Route::delete('/{config}', [AppStorageController::class, 'destroy'])->name('destroy');
            Route::post('/{config}/test', [AppStorageController::class, 'test'])->name('test');
            Route::post('/test-connection', [AppStorageController::class, 'testConnection'])->name('test-connection');
            Route::get('/analytics', [AppStorageAnalyticsController::class, 'index'])->name('analytics');
        });

        // ========== Backups ==========
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::get('/create', [BackupController::class, 'create'])->name('create');
            Route::post('/', [BackupController::class, 'store'])->name('store');
            Route::get('/{backup}', [BackupController::class, 'show'])->name('show');
            Route::delete('/{backup}', [BackupController::class, 'destroy'])->name('destroy');
            Route::get('/{backup}/download', [BackupController::class, 'download'])->name('download');
            Route::post('/{backup}/restore', [BackupController::class, 'restore'])->name('restore');
            Route::get('/{backup}/status', [BackupController::class, 'status'])->name('status');
            Route::post('/{backup}/run', [BackupController::class, 'run'])->name('run');
        });

        Route::prefix('backup-schedules')->name('backup-schedules.')->group(function () {
            Route::get('/', [BackupScheduleController::class, 'index'])->name('index');
            Route::get('/create', [BackupScheduleController::class, 'create'])->name('create');
            Route::post('/', [BackupScheduleController::class, 'store'])->name('store');
            Route::get('/{schedule}/edit', [BackupScheduleController::class, 'edit'])->name('edit');
            Route::put('/{schedule}', [BackupScheduleController::class, 'update'])->name('update');
            Route::delete('/{schedule}', [BackupScheduleController::class, 'destroy'])->name('destroy');
            Route::post('/{schedule}/execute', [BackupScheduleController::class, 'execute'])->name('execute');
            Route::post('/{schedule}/toggle-active', [BackupScheduleController::class, 'toggleActive'])->name('toggle-active');
        });

        Route::prefix('backup-storage')->name('backup-storage.')->group(function () {
            Route::get('/', [BackupStorageController::class, 'index'])->name('index');
            Route::get('/create', [BackupStorageController::class, 'create'])->name('create');
            Route::post('/', [BackupStorageController::class, 'store'])->name('store');
            Route::get('/{config}/edit', [BackupStorageController::class, 'edit'])->name('edit');
            Route::put('/{config}', [BackupStorageController::class, 'update'])->name('update');
            Route::delete('/{config}', [BackupStorageController::class, 'destroy'])->name('destroy');
            Route::post('/{config}/test', [BackupStorageController::class, 'test'])->name('test');
            Route::post('/test-connection', [BackupStorageController::class, 'testConnection'])->name('test-connection');
            Route::get('/analytics', [BackupStorageAnalyticsController::class, 'index'])->name('analytics');
        });

        Route::prefix('storage-disk-mappings')->name('storage-disk-mappings.')->group(function () {
            Route::get('/', [StorageDiskMappingController::class, 'index'])->name('index');
            Route::get('/create', [StorageDiskMappingController::class, 'create'])->name('create');
            Route::post('/', [StorageDiskMappingController::class, 'store'])->name('store');
            Route::get('/{mapping}/edit', [StorageDiskMappingController::class, 'edit'])->name('edit');
            Route::put('/{mapping}', [StorageDiskMappingController::class, 'update'])->name('update');
            Route::delete('/{mapping}', [StorageDiskMappingController::class, 'destroy'])->name('destroy');
        });

        // ========== AI ==========
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::resource('models', AIModelController::class)->names(['index' => 'models.index', 'create' => 'models.create', 'store' => 'models.store', 'show' => 'models.show', 'edit' => 'models.edit', 'update' => 'models.update', 'destroy' => 'models.destroy']);
            Route::post('models/{model}/test', [AIModelController::class, 'test'])->name('models.test');
            Route::post('models/test-temp', [AIModelController::class, 'testTemp'])->name('models.test-temp');
            Route::post('models/{model}/set-default', [AIModelController::class, 'setDefault'])->name('models.set-default');
            Route::post('models/{model}/toggle-active', [AIModelController::class, 'toggleActive'])->name('models.toggle-active');
            Route::post('models/fetch-groq-models', [AIModelController::class, 'fetchGroqModels'])->name('models.fetch-groq-models');
            Route::post('content/summarize', [AIContentController::class, 'summarize'])->name('content.summarize');
            Route::post('content/improve', [AIContentController::class, 'improve'])->name('content.improve');
            Route::post('content/grammar-check', [AIContentController::class, 'grammarCheck'])->name('content.grammar-check');
            Route::get('settings', [AISettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [AISettingsController::class, 'update'])->name('settings.update');
        });

        // ========== WhatsApp ==========
        Route::prefix('whatsapp-settings')->middleware(['role:admin'])->name('whatsapp-settings.')->group(function () {
            Route::get('/', [WhatsAppSettingsController::class, 'index'])->name('index');
            Route::post('/', [WhatsAppSettingsController::class, 'update'])->name('update');
            Route::post('/test-connection', [WhatsAppSettingsController::class, 'testConnection'])->name('test-connection');
        });
        Route::prefix('whatsapp-messages')->middleware(['role:admin'])->name('whatsapp-messages.')->group(function () {
            Route::get('/', [WhatsAppMessageController::class, 'index'])->name('index');
            Route::get('/send', [WhatsAppMessageController::class, 'create'])->name('create');
            Route::get('/search-students', [WhatsAppMessageController::class, 'searchStudents'])->name('search-students');
            Route::post('/send', [WhatsAppMessageController::class, 'send'])->name('send');
            Route::post('/broadcast', [WhatsAppMessageController::class, 'broadcast'])->name('broadcast');
            Route::get('/broadcast/students-count', [WhatsAppMessageController::class, 'getStudentsCount'])->name('broadcast.students-count');
            Route::post('/{message}/retry', [WhatsAppMessageController::class, 'retry'])->name('retry');
            Route::get('/{message}', [WhatsAppMessageController::class, 'show'])->name('show');
        });
        Route::prefix('whatsapp-web')->middleware(['role:admin'])->name('whatsapp-web.')->group(function () {
            Route::get('/connect', [WhatsAppWebController::class, 'connect'])->name('connect');
            Route::post('/start-connection', [WhatsAppWebController::class, 'startConnection'])->name('start-connection');
            Route::get('/qr/{sessionId}', [WhatsAppWebController::class, 'getQrCode'])->name('qr');
            Route::get('/status/{sessionId}', [WhatsAppWebController::class, 'getStatus'])->name('status');
            Route::post('/disconnect/{sessionId}', [WhatsAppWebController::class, 'disconnect'])->name('disconnect');
        });
        Route::prefix('whatsapp-web-settings')->middleware(['role:admin'])->name('whatsapp-web-settings.')->group(function () {
            Route::get('/', [WhatsAppWebSettingsController::class, 'index'])->name('index');
            Route::post('/', [WhatsAppWebSettingsController::class, 'update'])->name('update');
            Route::post('/test-connection', [WhatsAppWebSettingsController::class, 'testConnection'])->name('test-connection');
        });
    });

    Route::post('/admin/users/{user}/impersonation-token', [\App\Http\Controllers\Admin\ClientImpersonationController::class, 'store'])
        ->name('admin.users.impersonation-token');

    // إدارة المستخدمين
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::put('/{user}/update-password', [UserController::class, 'updatePassword'])->name('update-password');
    });

    // إدارة الأدوار
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{id}', [RoleController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
    });

    // الملف الشخصي
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
});
