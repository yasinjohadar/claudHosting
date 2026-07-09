<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GlobalSeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function __construct(protected GlobalSeoService $globalSeo)
    {
        $this->middleware('auth');
    }

    /**
     * عرض صفحة الإعدادات (نموذج واحد لجميع المفاتيح).
     */
    public function index()
    {
        $settings = Setting::getAllKeyValue();
        $homepageSeo = $this->globalSeo->homepageSeo();
        $homepageFallbackH1 = $this->globalSeo->resolve()['homepage_fallback_h1']
            ?? $this->globalSeo->defaults()['homepage_fallback_h1'];
        $homepagePreview = [
            'meta_title' => $this->globalSeo->replaceSitePlaceholders($homepageSeo['meta_title'] ?? ''),
            'meta_description' => $this->globalSeo->replaceSitePlaceholders($homepageSeo['meta_description'] ?? ''),
        ];

        return view('admin.settings.index', compact('settings', 'homepageSeo', 'homepageFallbackH1', 'homepagePreview'));
    }

    /**
     * حفظ التعديلات.
     */
    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'nullable|string|max:255',
            'footer_description' => 'nullable|string|max:2000',
            'copyright_text' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'contact_address' => 'nullable|string|max:255',
            'contact_work_hours' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|url|max:500',
            'social_youtube' => 'nullable|url|max:500',
            'social_instagram' => 'nullable|url|max:500',
            'social_linkedin' => 'nullable|url|max:500',
            'social_github' => 'nullable|url|max:500',
            'social_telegram' => 'nullable|url|max:500',
            'contact_form_action' => 'nullable|url|max:500',
            'homepage.meta_title' => 'nullable|string|max:120',
            'homepage.meta_description' => 'nullable|string|max:320',
            'homepage.meta_keywords' => 'nullable|string|max:500',
            'homepage.og_title' => 'nullable|string|max:120',
            'homepage.og_description' => 'nullable|string|max:320',
            'homepage.robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
            'homepage_fallback_h1' => 'nullable|string|max:255',
        ]);

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

        $this->globalSeo->saveHomepage(array_merge(
            $request->input('homepage', []),
            ['homepage_fallback_h1' => $request->input('homepage_fallback_h1')]
        ));

        return redirect()->route('admin.settings.index')
            ->with('success', 'تم حفظ إعدادات الموقع بنجاح.');
    }
}
