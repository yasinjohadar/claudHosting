<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/frontend.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'role-list' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'check.user.active' => \App\Http\Middleware\CheckUserActive::class,
            'admin.panel' => \App\Http\Middleware\EnsureAdminPanelUser::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user?->isAdminPanelUser()) {
                return route('admin.dashboard');
            }

            if ($user) {
                return route('client.dashboard');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();