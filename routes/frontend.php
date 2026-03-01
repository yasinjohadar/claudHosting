<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\PackageController;
use App\Http\Controllers\Frontend\DomainSearchController;

/*
|--------------------------------------------------------------------------
| Frontend Routes (الفرونت اند — الموقع العام)
|--------------------------------------------------------------------------
| مسارات الصفحات العامة بدون بادئة /admin.
| لوحة التحكم (الداشبورد) فقط تحت /admin في web.php.
|
*/

Route::prefix('')->name('frontend.')->group(function () {
    // الصفحات الداخلية للفرونت اند (الرئيسية / من web.php)
    // الباقات (ديناميكي من قاعدة البيانات)
    Route::get('/packages', [PackageController::class, 'index'])->name('packages');
    Route::get('/packages/{id}/order', [PackageController::class, 'orderForm'])->name('package.order.form')->middleware('auth');
    Route::post('/packages/order', [PackageController::class, 'storeOrder'])->name('package.order.store')->middleware('auth');
    Route::get('/packages/{id}', [PackageController::class, 'show'])->name('package-detail');

    Route::get('/domain-search', [DomainSearchController::class, 'index'])->name('domain-search');
    Route::post('/domain-search', [DomainSearchController::class, 'search'])->name('domain-search.post');

    Route::get('/courses', function () {
        return redirect()->route('frontend.packages');
    })->name('courses');

    Route::get('/about', function () {
        return view('frontend.pages.about');
    })->name('about');

    Route::get('/projects', function () {
        return view('frontend.pages.projects');
    })->name('projects');

    // المدونة — ديناميكي من لوحة التحكم
    Route::get('/blog', [BlogController::class, 'index'])->name('blog');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

    Route::get('/contact', function () {
        return view('frontend.pages.contact');
    })->name('contact');

    Route::get('/videos', function () {
        return view('frontend.pages.videos');
    })->name('videos');

    Route::get('/consultation', function () {
        return view('frontend.pages.consultation');
    })->name('consultation');

    Route::get('/testimonials', function () {
        return view('frontend.pages.testimonials');
    })->name('testimonials');

    Route::get('/clients', function () {
        return view('frontend.pages.clients');
    })->name('clients');

    Route::get('/service-detail', function () {
        return view('frontend.pages.service-detail');
    })->name('service-detail');

    Route::get('/service-detail-mobile', function () {
        return view('frontend.pages.service-detail-mobile');
    })->name('service-detail-mobile');

    Route::get('/service-detail-security', function () {
        return view('frontend.pages.service-detail-security');
    })->name('service-detail-security');

    Route::get('/service-detail-servers', function () {
        return view('frontend.pages.service-detail-servers');
    })->name('service-detail-servers');

    Route::get('/service-detail-devops', function () {
        return view('frontend.pages.service-detail-devops');
    })->name('service-detail-devops');

    // صفحة تفاصيل الباقة أصبحت ديناميكية عبر /packages/{id}
    Route::get('/project-detail', function () {
        return view('frontend.pages.project-detail');
    })->name('project-detail');
});
