<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class EnsureAdminRoleCommand extends Command
{
    protected $signature = 'auth:ensure-admin {email : بريد مستخدم لوحة الإدارة}';

    protected $description = 'تعيين دور admin لمستخدم (إصلاح سريع على السيرفر)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("المستخدم غير موجود: {$email}");

            return self::FAILURE;
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        if ($user->hasRole('client') && ! $user->hasRole('admin')) {
            $user->removeRole('client');
        }

        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        $user->update([
            'is_active' => true,
            'status' => 'active',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info("تم تعيين دور admin لـ {$email}");
        $this->line('isAdminPanelUser: '.($user->fresh()->isAdminPanelUser() ? 'YES' : 'NO'));

        return self::SUCCESS;
    }
}
