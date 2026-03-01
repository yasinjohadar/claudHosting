<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // تسجيل Blade Directives للصلاحيات
        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->hasPermissionTo($permission)): ?>";
        });

        Blade::directive('endpermission', function () {
            return "<?php endif; ?>";
        });

        // تسجيل Blade Directives للأدوار
        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole($role)): ?>";
        });

        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });

        // تسجيل Blade Directives لأي دور
        Blade::directive('hasanyrole', function ($roles) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyRole($roles)): ?>";
        });

        Blade::directive('endhasanyrole', function () {
            return "<?php endif; ?>";
        });

        // تسجيل Gates للصلاحيات
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // تسجيل Gates ديناميكية للصلاحيات
        try {
            Permission::get()->map(function ($permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    return $user->hasPermissionTo($permission);
                });
            });
        } catch (\Exception $e) {
            // تجاهل الأخطاء أثناء التطوير
        }
    }
} 