<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض صفحة الإعدادات (نموذج واحد لجميع المفاتيح).
     */
    public function index()
    {
        $settings = Setting::getAllKeyValue();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * حفظ التعديلات.
     */
    public function update(Request $request)
    {
        $keys = [
            'contact_email',
            'contact_phone',
            'contact_whatsapp',
            'contact_address',
            'contact_work_hours',
            'social_facebook',
            'social_youtube',
            'social_instagram',
            'social_linkedin',
            'social_github',
            'social_telegram',
            'site_name',
            'footer_description',
            'copyright_text',
            'contact_form_action',
        ];

        foreach ($keys as $key) {
            $value = $request->input($key);
            Setting::set($key, $value !== null ? (string) $value : null);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
