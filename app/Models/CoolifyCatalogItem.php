<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoolifyCatalogItem extends Model
{
    protected $fillable = [
        'slug',
        'category',
        'coolify_key',
        'name_ar',
        'description_ar',
        'icon',
        'enabled',
        'featured',
        'sort_order',
        'install_steps',
        'requirements',
        'docs_url',
        'is_custom',
        'install_mode',
        'custom_install_url',
        'available_on_coolify',
        'from_config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'featured' => 'boolean',
        'install_steps' => 'array',
        'requirements' => 'array',
        'is_custom' => 'boolean',
        'available_on_coolify' => 'boolean',
        'from_config' => 'boolean',
    ];
}
