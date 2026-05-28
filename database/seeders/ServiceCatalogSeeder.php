<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'بريد إلكتروني', 'description' => 'حسابات بريد واستضافة بريد'],
            ['name' => 'تصميم', 'description' => 'تصميم مواقع وشعارات وواجهات'],
            ['name' => 'نقل مواقع', 'description' => 'نقل وترحيل المواقع والبيانات'],
            ['name' => 'دعم فني', 'description' => 'خدمات دعم وصيانة تقنية'],
        ];

        foreach ($types as $index => $type) {
            ServiceType::firstOrCreate(
                ['slug' => Str::slug($type['name'], '-', 'ar')],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
