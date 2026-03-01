<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PackageOrderRequestController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\WhmcsTestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\WhmcsExampleController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\AppStorageController;
use App\Http\Controllers\Admin\AppStorageAnalyticsController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BackupScheduleController;
use App\Http\Controllers\Admin\BackupStorageController;
use App\Http\Controllers\Admin\BackupStorageAnalyticsController;
use App\Http\Controllers\Admin\StorageDiskMappingController;
use App\Http\Controllers\Admin\WhatsAppSettingsController;
use App\Http\Controllers\Admin\WhatsAppMessageController;
use App\Http\Controllers\Admin\WhatsAppWebController;
use App\Http\Controllers\Admin\WhatsAppWebSettingsController;
use App\Http\Controllers\Admin\AIBlogPostController;
use App\Http\Controllers\Admin\AIModelController;
use App\Http\Controllers\Admin\AIContentController;
use App\Http\Controllers\Admin\AISettingsController;
use App\Http\Controllers\Admin\SettingController;

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
    return view('frontend.pages.index', compact('latestBlogPosts', 'featuredPackages'));
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

// المسارات المحمية
Route::middleware(['auth'])->group(function () {
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
            Route::post('/{id}/sync', [CustomerController::class, 'sync'])->name('sync');
            Route::post('/{id}/sync-products', [CustomerController::class, 'syncProducts'])->name('syncProducts');
            Route::post('/{id}/sync-contacts', [CustomerController::class, 'syncContacts'])->name('syncContacts');
            Route::post('/{id}/sync-full', [CustomerController::class, 'syncFull'])->name('syncFull');
            Route::post('/{id}/products/{serviceId}/suspend', [CustomerController::class, 'productSuspend'])->name('productSuspend');
            Route::post('/{id}/products/{serviceId}/unsuspend', [CustomerController::class, 'productUnsuspend'])->name('productUnsuspend');
            Route::post('/{id}/products/{serviceId}/terminate', [CustomerController::class, 'productTerminate'])->name('productTerminate');
            Route::post('/sync-all', [CustomerController::class, 'syncAll'])->name('syncAll');
        });

        // إدارة المنتجات
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{id}', [ProductController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/sync', [ProductController::class, 'sync'])->name('sync');
            Route::post('/sync-all', [ProductController::class, 'syncAll'])->name('syncAll');
        });

        // طلبات الباقات (من الفرونت اند)
        Route::prefix('order-requests')->name('order-requests.')->group(function () {
            Route::get('/', [PackageOrderRequestController::class, 'index'])->name('index');
            Route::get('/{id}', [PackageOrderRequestController::class, 'show'])->name('show');
            Route::put('/{id}', [PackageOrderRequestController::class, 'update'])->name('update');
            Route::post('/{id}/convert-to-whmcs', [PackageOrderRequestController::class, 'convertToWhmcs'])->name('convert-to-whmcs');
        });

        // إعدادات الموقع (تواصل، سوشيال، عام)
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        // إدارة الفواتير
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
            Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [InvoiceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [InvoiceController::class, 'update'])->name('update');
            Route::delete('/{id}', [InvoiceController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/sync', [InvoiceController::class, 'sync'])->name('sync');
            Route::post('/sync-all', [InvoiceController::class, 'syncAll'])->name('syncAll');
            Route::post('/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('markPaid');
            Route::post('/{id}/add-payment', [InvoiceController::class, 'addPayment'])->name('addPayment');
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
            Route::post('/{id}/sync', [TicketController::class, 'sync'])->name('sync');
            Route::post('/sync-all', [TicketController::class, 'syncAll'])->name('syncAll');
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

        // اختبار WHMCS
        Route::prefix('whmcs')->name('whmcs.')->group(function () {
            Route::get('/test', [WhmcsTestController::class, 'index'])->name('test');
            Route::get('/test-currencies', [WhmcsExampleController::class, 'testConnection'])->name('testCurrencies');
            Route::get('/debug', [WhmcsExampleController::class, 'debug'])->name('debug');
            Route::post('/test-connection', [WhmcsTestController::class, 'testConnection'])->name('testConnection');
            Route::post('/sync-customers', [WhmcsTestController::class, 'syncCustomers'])->name('syncCustomers');
            Route::post('/sync-products', [WhmcsTestController::class, 'syncProducts'])->name('syncProducts');
            Route::post('/sync-invoices', [WhmcsTestController::class, 'syncInvoices'])->name('syncInvoices');
            Route::post('/sync-tickets', [WhmcsTestController::class, 'syncTickets'])->name('syncTickets');
            Route::get('/sync', [WhmcsTestController::class, 'syncPage'])->name('sync');
            Route::post('/full-sync', [WhmcsTestController::class, 'fullSync'])->name('fullSync');
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
            Route::resource('models', AIModelController::class)->names(['index'=>'models.index','create'=>'models.create','store'=>'models.store','show'=>'models.show','edit'=>'models.edit','update'=>'models.update','destroy'=>'models.destroy']);
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
