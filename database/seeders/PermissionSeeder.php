<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // صلاحيات الأدوار
            "role-list",
            "role-create",
            "role-edit",
            "role-delete",

            // صلاحيات المستخدمين
            "user-list",
            "user-create",
            "user-edit",
            "user-delete",
            "user-show",


            // صلاحيات إضافية للنظام
            "dashboard-view",
            "settings-manage",
            "reports-view",
        ];

        foreach ($permissions as $key => $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
