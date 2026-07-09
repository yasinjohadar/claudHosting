<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSeoService;
use App\Services\PageSeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageSeoSettingsController extends Controller
{
    public function __construct(
        protected PageSeoService $pageSeo,
        protected GlobalSeoService $globalSeo
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

        $global = $this->globalSeo->resolve();
        $global['default_og_image_url'] = $this->globalSeo->resolveImageUrl($global['default_og_image'] ?? null);
        $global['organization_logo_url'] = $this->globalSeo->resolveImageUrl($global['organization']['logo'] ?? null);
        $global['robots']['disallow_paths_text'] = implode("\n", $global['robots']['disallow_paths'] ?? []);
        $global['homepage'] = $this->globalSeo->homepageSeo();
        $global['homepage_preview'] = [
            'meta_title' => $this->globalSeo->replaceSitePlaceholders($global['homepage']['meta_title'] ?? ''),
            'meta_description' => $this->globalSeo->replaceSitePlaceholders($global['homepage']['meta_description'] ?? ''),
        ];

        return view('admin.homepage.seo.index', compact('pages', 'configs', 'global'));
    }

    public function update(Request $request)
    {
        if ($request->input('form_section') === 'global') {
            return $this->updateGlobal($request);
        }

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
            ->route('admin.homepage.seo.index', ['tab' => 'pages'])
            ->with('success', 'تم حفظ إعدادات SEO للصفحات بنجاح.');
    }

    protected function updateGlobal(Request $request)
    {
        $tab = $request->input('global_tab', 'general');

        $rules = match ($tab) {
            'blog' => [
                'blog.paginated_title_template' => 'nullable|string|max:120',
                'blog.paginated_robots' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
            ],
            'robots' => [
                'robots.disallow_paths' => 'nullable|string|max:5000',
            ],
            default => [
                'organization.legal_name' => 'nullable|string|max:255',
                'organization.url' => 'nullable|url|max:500',
                'twitter_site' => 'nullable|string|max:100',
                'twitter_card_default' => 'nullable|string|max:50',
                'search_action_url_template' => 'nullable|string|max:500',
                'default_og_image' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
                'organization_logo' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
            ],
        };

        $validated = $request->validate($rules);

        $payload = $validated;
        if ($tab === 'robots') {
            $payload['robots'] = [
                'disallow_paths' => $request->input('robots.disallow_paths', ''),
            ];
        }
        if ($tab === 'blog') {
            $payload['blog'] = $request->input('blog', []);
        }

        $this->globalSeo->save($payload, $request);

        return redirect()
            ->route('admin.homepage.seo.index', ['tab' => $tab])
            ->with('success', 'تم حفظ الإعدادات العامة لـ SEO بنجاح.');
    }

    public function reset(Request $request)
    {
        if ($request->input('reset_global')) {
            $this->globalSeo->resetToDefaults();

            return redirect()
                ->route('admin.homepage.seo.index', ['tab' => 'general'])
                ->with('success', 'تمت استعادة الإعدادات العامة للقيم الافتراضية.');
        }

        $route = $request->input('route');
        $allowed = array_keys($this->pageSeo->manageablePagesForAdmin());
        if (! is_string($route) || ! in_array($route, $allowed, true)) {
            abort(404);
        }

        $this->pageSeo->resetRouteToDefaults($route);

        return redirect()
            ->route('admin.homepage.seo.index', ['tab' => 'pages'])
            ->with('success', 'تمت استعادة القيم الافتراضية للصفحة.');
    }
}
