<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageDiskMapping;
use App\Models\AppStorageConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StorageDiskMappingController extends Controller
{
    /**
     * قائمة Disk Mappings
     */
    public function index()
    {
        $mappings = StorageDiskMapping::with('primaryStorage')
                                      ->orderBy('disk_name')
                                      ->get();

        return view('admin.pages.storage-disk-mappings.index', compact('mappings'));
    }

    /**
     * إنشاء Disk Mapping
     */
    public function create()
    {
        $storages = AppStorageConfig::where('is_active', true)
                                    ->orderBy('priority', 'desc')
                                    ->get();

        return view('admin.pages.storage-disk-mappings.create', compact('storages'));
    }

    /**
     * حفظ Disk Mapping
     */
    public function store(Request $request)
    {
        Log::info('StorageDiskMappingController: store method called', [
            'request_data' => $request->except(['_token']),
        ]);

        try {
            $validated = $request->validate([
                'disk_name' => 'required|string|max:255|unique:storage_disk_mappings,disk_name',
                'label' => 'required|string|max:255',
                'primary_storage_id' => 'required|exists:app_storage_configs,id',
                'fallback_storage_ids' => 'nullable|array',
                'fallback_storage_ids.*' => 'exists:app_storage_configs,id',
                'file_types' => 'nullable|array',
            ]);

            Log::info('StorageDiskMappingController: Validation passed', [
                'validated_data' => $validated,
            ]);

            $mapping = StorageDiskMapping::create([
                'disk_name' => $validated['disk_name'],
                'label' => $validated['label'],
                'primary_storage_id' => $validated['primary_storage_id'],
                'fallback_storage_ids' => $request->input('fallback_storage_ids'),
                'file_types' => $request->input('file_types'),
                'is_active' => $request->has('is_active'),
            ]);

            Log::info('StorageDiskMappingController: Mapping created successfully', [
                'mapping_id' => $mapping->id,
                'disk_name' => $mapping->disk_name,
            ]);

            return redirect()->route('admin.storage-disk-mappings.index')
                           ->with('success', 'تم إنشاء Disk Mapping بنجاح.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('StorageDiskMappingController: Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['_token']),
            ]);
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('StorageDiskMappingController: Error creating disk mapping', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token']),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء إنشاء Disk Mapping: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * تعديل Disk Mapping
     */
    public function edit(StorageDiskMapping $mapping)
    {
        $storages = AppStorageConfig::where('is_active', true)
                                    ->orderBy('priority', 'desc')
                                    ->get();

        return view('admin.pages.storage-disk-mappings.edit', compact('mapping', 'storages'));
    }

    /**
     * تحديث Disk Mapping
     */
    public function update(Request $request, StorageDiskMapping $mapping)
    {
        Log::info('StorageDiskMappingController: update method called', [
            'mapping_id' => $mapping->id,
            'request_data' => $request->except(['_token', '_method']),
        ]);

        try {
            $validated = $request->validate([
                'disk_name' => 'required|string|max:255|unique:storage_disk_mappings,disk_name,' . $mapping->id,
                'label' => 'required|string|max:255',
                'primary_storage_id' => 'required|exists:app_storage_configs,id',
                'fallback_storage_ids' => 'nullable|array',
                'fallback_storage_ids.*' => 'exists:app_storage_configs,id',
                'file_types' => 'nullable|array',
            ]);

            Log::info('StorageDiskMappingController: Validation passed', [
                'validated_data' => $validated,
            ]);

            $mapping->update([
                'disk_name' => $validated['disk_name'],
                'label' => $validated['label'],
                'primary_storage_id' => $validated['primary_storage_id'],
                'fallback_storage_ids' => $request->input('fallback_storage_ids'),
                'file_types' => $request->input('file_types'),
                'is_active' => $request->has('is_active'),
            ]);

            Log::info('StorageDiskMappingController: Mapping updated successfully', [
                'mapping_id' => $mapping->id,
                'disk_name' => $mapping->disk_name,
            ]);

            return redirect()->route('admin.storage-disk-mappings.index')
                           ->with('success', 'تم تحديث Disk Mapping بنجاح.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('StorageDiskMappingController: Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['_token', '_method']),
            ]);
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('StorageDiskMappingController: Error updating disk mapping', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token', '_method']),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحديث Disk Mapping: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * حذف Disk Mapping
     */
    public function destroy(StorageDiskMapping $mapping)
    {
        try {
            $mapping->delete();

            return redirect()->route('admin.storage-disk-mappings.index')
                           ->with('success', 'تم حذف Disk Mapping بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error deleting disk mapping: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف Disk Mapping: ' . $e->getMessage());
        }
    }
}
