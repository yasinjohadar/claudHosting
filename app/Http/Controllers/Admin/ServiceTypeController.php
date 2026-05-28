<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServiceTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $serviceTypes = ServiceType::withCount('offeredServices')
            ->ordered()
            ->paginate(15);

        return view('admin.service-types.index', compact('serviceTypes'));
    }

    public function create()
    {
        return view('admin.service-types.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateType($request);

        $slug = $validated['slug'] ?? Str::slug($validated['name'], '-', 'ar');
        $validated['slug'] = ServiceType::uniqueSlug($slug ?: 'type');
        $validated['is_active'] = $request->boolean('is_active', true);

        ServiceType::create($validated);

        return redirect()->route('admin.service-types.index')
            ->with('success', 'تم إنشاء نوع الخدمة بنجاح');
    }

    public function edit(ServiceType $serviceType)
    {
        return view('admin.service-types.edit', compact('serviceType'));
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $validated = $this->validateType($request, $serviceType->id);

        if (! empty($validated['slug'])) {
            $validated['slug'] = ServiceType::uniqueSlug($validated['slug']);
        } else {
            unset($validated['slug']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $serviceType->update($validated);

        return redirect()->route('admin.service-types.index')
            ->with('success', 'تم تحديث نوع الخدمة بنجاح');
    }

    public function destroy(ServiceType $serviceType)
    {
        if ($serviceType->offeredServices()->exists()) {
            return redirect()->route('admin.service-types.index')
                ->with('error', 'لا يمكن حذف نوع مرتبط بخدمات. انقل الخدمات أو احذفها أولاً.');
        }

        $serviceType->delete();

        return redirect()->route('admin.service-types.index')
            ->with('success', 'تم حذف نوع الخدمة بنجاح');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateType(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'nullable|string|max:255|unique:service_types,slug';
        if ($ignoreId) {
            $slugRule .= ','.$ignoreId;
        }

        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ])->validate();
    }
}
