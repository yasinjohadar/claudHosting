<?php

namespace Database\Seeders;

use App\Models\CoolifyCatalogItem;
use Illuminate\Database\Seeder;

class CoolifyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('coolify_catalog.items', []) as $item) {
            CoolifyCatalogItem::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category' => $item['category'],
                    'coolify_key' => $item['coolify_key'] ?? null,
                    'name_ar' => $item['name_ar'],
                    'description_ar' => $item['description_ar'] ?? null,
                    'icon' => $item['icon'] ?? 'fe-box',
                    'enabled' => $item['enabled'] ?? true,
                    'featured' => $item['featured'] ?? false,
                    'sort_order' => $item['sort_order'] ?? 100,
                    'install_steps' => $item['install_steps'] ?? [],
                    'requirements' => $item['requirements'] ?? [],
                    'docs_url' => $item['docs_url'] ?? null,
                    'is_custom' => $item['is_custom'] ?? false,
                    'install_mode' => $item['install_mode'] ?? null,
                    'custom_install_url' => $item['custom_install_url'] ?? null,
                    'from_config' => true,
                ]
            );
        }
    }
}
