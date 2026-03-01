<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // تشغيل seeder الصلاحيات أولاً
        $this->call([
            PermissionSeeder::class,
            AdminUserSeeder::class,
            BlogSeeder::class,
        ]);

        // إنشاء مستخدم تجريبي إضافي
        User::factory()->create([
            'name' => 'user',
            'email' => 'user@example.com',
        ]);
    }
}
