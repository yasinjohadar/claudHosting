<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء جميع الصلاحيات
        $permissions = [
            // صلاحيات العملاء
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.sync',
            // صلاحيات المنتجات
            'products.view', 'products.create', 'products.edit', 'products.delete', 'products.sync',
            // صلاحيات الفواتير
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.sync',
            // صلاحيات التذاكر
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.delete', 'tickets.sync',
            // صلاحيات التقارير
            'reports.view',
            // صلاحيات المستخدمين
            'user-list', 'user-create', 'user-edit', 'user-delete', 'user-show',
            // صلاحيات الأدوار
            'role-list', 'role-create', 'role-edit', 'role-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // إنشاء دور admin
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        
        // تعيين جميع الصلاحيات لدور admin
        $adminRole->syncPermissions(Permission::all());

        // حذف المستخدم القديم إذا كان موجوداً
        User::where('email', 'admin@gmail.com')->delete();

        // إنشاء مستخدم admin جديد
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456789'),
            'status' => 'active',
            'is_active' => true,
        ]);

        // تعيين دور admin للمستخدم
        $admin->assignRole('admin');

        $this->command->info('تم إنشاء مستخدم Admin بنجاح!');
        $this->command->info('البريد: admin@gmail.com');
        $this->command->info('كلمة المرور: 123456789');
    }
}
