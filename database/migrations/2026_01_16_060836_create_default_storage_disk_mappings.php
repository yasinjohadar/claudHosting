<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\AppStorageConfig;
use App\Models\StorageDiskMapping;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إنشاء Local Storage Config افتراضي إذا لم يكن موجوداً
        $localStorage = AppStorageConfig::where('driver', 'local')
            ->where('name', 'Local Storage (Default)')
            ->first();

        if (!$localStorage) {
            $localStorage = AppStorageConfig::create([
                'name' => 'Local Storage (Default)',
                'driver' => 'local',
                'config' => json_encode(['path' => 'public']),
                'is_active' => true,
                'priority' => 100,
            ]);
        }

        // إنشاء default mapping لـ 'public' disk إذا لم يكن موجوداً
        $publicMapping = StorageDiskMapping::where('disk_name', 'public')->first();
        if (!$publicMapping) {
            StorageDiskMapping::create([
                'disk_name' => 'public',
                'label' => 'Public Storage (Default)',
                'primary_storage_id' => $localStorage->id,
                'fallback_storage_ids' => null,
                'file_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
                'is_active' => true,
            ]);
        }

        // إنشاء default mapping لـ 'images' disk إذا لم يكن موجوداً
        $imagesMapping = StorageDiskMapping::where('disk_name', 'images')->first();
        if (!$imagesMapping) {
            StorageDiskMapping::create([
                'disk_name' => 'images',
                'label' => 'Images Storage',
                'primary_storage_id' => $localStorage->id,
                'fallback_storage_ids' => null,
                'file_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف mappings الافتراضية (اختياري - يمكن تركها)
        StorageDiskMapping::where('disk_name', 'public')
            ->where('label', 'Public Storage (Default)')
            ->delete();

        StorageDiskMapping::where('disk_name', 'images')
            ->where('label', 'Images Storage')
            ->delete();

        // حذف Local Storage Config الافتراضي (اختياري - يمكن تركها)
        AppStorageConfig::where('name', 'Local Storage (Default)')
            ->where('driver', 'local')
            ->delete();
    }
};
