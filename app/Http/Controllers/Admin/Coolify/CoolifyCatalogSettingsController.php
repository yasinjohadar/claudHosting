<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Controller;
use App\Models\CoolifyCatalogItem;
use App\Services\Coolify\CoolifyCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CoolifyCatalogSettingsController extends Controller
{
    public function __construct(
        protected CoolifyCatalogService $catalog
    ) {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $items = $this->catalog->getCatalog(false);

        return view('admin.coolify.catalog.settings', [
            'items' => $items,
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $model = CoolifyCatalogItem::query()->findOrFail($id);

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'enabled' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'install_steps_text' => 'nullable|string',
            'requirements_text' => 'nullable|string',
            'docs_url' => 'nullable|url|max:500',
        ]);

        $model->fill([
            'name_ar' => $validated['name_ar'],
            'description_ar' => $validated['description_ar'] ?? null,
            'enabled' => $request->boolean('enabled'),
            'featured' => $request->boolean('featured'),
            'sort_order' => (int) ($validated['sort_order'] ?? $model->sort_order),
            'docs_url' => $validated['docs_url'] ?? null,
            'install_steps' => $this->linesToArray($validated['install_steps_text'] ?? ''),
            'requirements' => $this->linesToArray($validated['requirements_text'] ?? ''),
        ]);
        $model->save();

        return back()->with('success', 'تم تحديث «'.$model->name_ar.'»');
    }

    public function storeCustom(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'category' => 'required|in:custom,service',
            'coolify_key' => 'nullable|string|max:128',
            'install_mode' => 'required|in:service,link,docs_only',
            'custom_install_url' => 'nullable|url|max:500',
            'enabled' => 'nullable|boolean',
            'docs_url' => 'nullable|url|max:500',
            'install_steps_text' => 'nullable|string',
            'requirements_text' => 'nullable|string',
        ]);

        $slug = 'custom-'.Str::slug($validated['name_ar'], '-');
        if (CoolifyCatalogItem::query()->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::random(4);
        }

        CoolifyCatalogItem::query()->create([
            'slug' => $slug,
            'category' => $validated['category'] === 'service' ? 'service' : 'custom',
            'coolify_key' => $validated['coolify_key'] ?? null,
            'name_ar' => $validated['name_ar'],
            'description_ar' => $validated['description_ar'] ?? null,
            'icon' => 'fe-star',
            'enabled' => $request->boolean('enabled', true),
            'featured' => false,
            'sort_order' => 900,
            'install_steps' => $this->linesToArray($validated['install_steps_text'] ?? ''),
            'requirements' => $this->linesToArray($validated['requirements_text'] ?? ''),
            'docs_url' => $validated['docs_url'] ?? null,
            'is_custom' => true,
            'install_mode' => $validated['install_mode'],
            'custom_install_url' => $validated['custom_install_url'] ?? null,
            'from_config' => false,
        ]);

        return back()->with('success', 'تمت إضافة المورد المخصص.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $model = CoolifyCatalogItem::query()->findOrFail($id);
        if ($model->from_config) {
            return back()->with('error', 'لا يمكن حذف عناصر الكتالوج الافتراضية — عطّلها فقط.');
        }
        $model->delete();

        return back()->with('success', 'تم الحذف.');
    }

    /**
     * @return array<int, string>
     */
    protected function linesToArray(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
    }
}
