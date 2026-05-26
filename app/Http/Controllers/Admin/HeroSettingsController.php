<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HeroSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HeroSettingsController extends Controller
{
    public function __construct(
        protected HeroSettingsService $heroSettings
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $hero = $this->heroSettings->get();

        return view('admin.homepage.hero.index', compact('hero'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'content.title_prefix' => 'required|string|max:120',
            'content.typing_texts' => 'nullable|string|max:2000',
            'content.subtitle' => 'required|string|max:2000',
            'content.image_alt' => 'nullable|string|max:255',
            'content.buttons' => 'nullable|array',
            'content.buttons.*.label' => 'nullable|string|max:120',
            'content.buttons.*.url' => 'nullable|string|max:500',
            'content.buttons.*.style' => ['nullable', Rule::in(['primary', 'outline'])],
            'content.buttons.*.icon' => 'nullable|string|max:80',
            'content.buttons.*.enabled' => 'nullable|boolean',
            'content.stats' => 'nullable|array',
            'content.stats.*.value' => 'nullable|integer|min:0|max:999999',
            'content.stats.*.suffix' => 'nullable|string|max:10',
            'content.stats.*.label' => 'nullable|string|max:120',
            'content.stats.*.enabled' => 'nullable|boolean',
            'light.background.mode' => ['nullable', Rule::in(['inherit', 'color', 'gradient', 'image'])],
            'light.background.color' => 'nullable|string|max:20',
            'light.background.gradient_from' => 'nullable|string|max:20',
            'light.background.gradient_to' => 'nullable|string|max:20',
            'light.background.gradient_angle' => 'nullable|integer|min:0|max:360',
            'dark.background.mode' => ['nullable', Rule::in(['inherit', 'color', 'gradient', 'image'])],
            'dark.background.color' => 'nullable|string|max:20',
            'dark.background.gradient_from' => 'nullable|string|max:20',
            'dark.background.gradient_to' => 'nullable|string|max:20',
            'dark.background.gradient_angle' => 'nullable|integer|min:0|max:360',
            'hero_image_light' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
            'hero_image_dark' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
            'background_image_light' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
            'background_image_dark' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
            'remove_hero_image_light' => 'nullable|boolean',
            'remove_hero_image_dark' => 'nullable|boolean',
            'remove_background_image_light' => 'nullable|boolean',
            'remove_background_image_dark' => 'nullable|boolean',
        ]);

        $payload = [
            'enabled' => $request->boolean('enabled', true),
            'content' => [
                'title_prefix' => $validated['content']['title_prefix'],
                'typing_texts' => $request->input('content.typing_texts', ''),
                'subtitle' => $validated['content']['subtitle'],
                'image_alt' => $request->input('content.image_alt', ''),
                'buttons' => $request->input('content.buttons', []),
                'stats' => $request->input('content.stats', []),
            ],
            'light' => [
                'background' => $request->input('light.background', []),
            ],
            'dark' => [
                'background' => $request->input('dark.background', []),
            ],
        ];

        $this->heroSettings->save($payload, $request);

        return redirect()
            ->route('admin.homepage.hero.index')
            ->with('success', 'تم حفظ إعدادات الهيرو بنجاح.');
    }
}
