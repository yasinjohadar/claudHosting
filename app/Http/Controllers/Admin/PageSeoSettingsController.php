<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PageSeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageSeoSettingsController extends Controller
{
    public function __construct(
        protected PageSeoService $pageSeo
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $pages = $this->pageSeo->manageablePagesForAdmin();
        $configs = [];
        foreach (array_keys($pages) as $routeName) {
            $configs[$routeName] = $this->pageSeo->getPageConfig($routeName);
            $configs[$routeName]['og_image_url'] = $this->pageSeo->resolveImageUrl(
                $configs[$routeName]['og_image'] ?? null
            );
        }

        return view('admin.homepage.seo.index', compact('pages', 'configs'));
    }

    public function update(Request $request)
    {
        $routeNames = array_keys($this->pageSeo->manageablePagesForAdmin());

        $rules = [
            'pages' => 'required|array',
        ];

        foreach ($routeNames as $route) {
            $prefix = "pages.{$route}";
            $rules["{$prefix}.meta_title"] = 'nullable|string|max:70';
            $rules["{$prefix}.meta_description"] = 'nullable|string|max:320';
            $rules["{$prefix}.meta_keywords"] = 'nullable|string|max:500';
            $rules["{$prefix}.robots"] = ['nullable', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])];
            $rules["{$prefix}.canonical"] = 'nullable|string|max:500';
            $rules["{$prefix}.og_title"] = 'nullable|string|max:70';
            $rules["{$prefix}.og_description"] = 'nullable|string|max:320';
            $rules["{$prefix}.og_type"] = 'nullable|string|max:50';
            $rules["{$prefix}.twitter_title"] = 'nullable|string|max:70';
            $rules["{$prefix}.twitter_description"] = 'nullable|string|max:320';
            $rules["{$prefix}.twitter_card"] = 'nullable|string|max:50';
            $rules["og_image_{$route}"] = 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096';
            $rules["remove_og_image_{$route}"] = 'nullable|boolean';
        }

        $validated = $request->validate($rules);

        $this->pageSeo->save($validated['pages'] ?? [], $request);

        return redirect()
            ->route('admin.homepage.seo.index')
            ->with('success', 'تم حفظ إعدادات SEO بنجاح.');
    }

    public function reset(Request $request)
    {
        $route = $request->input('route');
        $allowed = array_keys($this->pageSeo->manageablePagesForAdmin());
        if (! is_string($route) || ! in_array($route, $allowed, true)) {
            abort(404);
        }

        $this->pageSeo->resetRouteToDefaults($route);

        return redirect()
            ->route('admin.homepage.seo.index')
            ->with('success', 'تمت استعادة القيم الافتراضية للصفحة.');
    }
}
