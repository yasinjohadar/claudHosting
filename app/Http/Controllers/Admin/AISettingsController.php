<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AISetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AISettingsController extends Controller
{
    /**
     * إعدادات AI
     */
    public function index()
    {
        $settings = AISetting::orderBy('key')->get();

        return view('admin.ai.settings.index', compact('settings'));
    }

    /**
     * تحديث الإعدادات
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'required|in:string,integer,boolean,json',
            'settings.*.description' => 'nullable|string',
            'settings.*.is_public' => 'boolean',
        ]);

        try {
            foreach ($validated['settings'] as $settingData) {
                AISetting::updateOrCreate(
                    ['key' => $settingData['key']],
                    [
                        'value' => $settingData['value'] ?? null,
                        'type' => $settingData['type'],
                        'description' => $settingData['description'] ?? null,
                        'is_public' => $settingData['is_public'] ?? false,
                    ]
                );
            }

            return redirect()->route('admin.ai.settings.index')
                           ->with('success', 'تم تحديث الإعدادات بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating AI settings: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage())
                           ->withInput();
        }
    }
}
