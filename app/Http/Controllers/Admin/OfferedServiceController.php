<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfferedService;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OfferedServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = OfferedService::with('serviceType')->ordered();

        if ($request->filled('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->paginate(15)->withQueryString();
        $serviceTypes = ServiceType::ordered()->get();

        return view('admin.offered-services.index', compact('services', 'serviceTypes'));
    }

    public function create()
    {
        $serviceTypes = ServiceType::active()->ordered()->get();

        return view('admin.offered-services.create', compact('serviceTypes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateService($request);

        $slug = $validated['slug'] ?? Str::slug($validated['name'], '-', 'ar');
        $validated['slug'] = OfferedService::uniqueSlug($slug ?: 'service');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        OfferedService::create($validated);

        return redirect()->route('admin.offered-services.index')
            ->with('success', 'تم إنشاء الخدمة بنجاح');
    }

    public function show(OfferedService $service)
    {
        $service->load('serviceType');

        return view('admin.offered-services.show', compact('service'));
    }

    public function edit(OfferedService $service)
    {
        $serviceTypes = ServiceType::ordered()->get();

        return view('admin.offered-services.edit', compact('service', 'serviceTypes'));
    }

    public function update(Request $request, OfferedService $service)
    {
        $validated = $this->validateService($request, $service->id);

        if (! empty($validated['slug'])) {
            $validated['slug'] = OfferedService::uniqueSlug($validated['slug']);
        } else {
            unset($validated['slug']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $service->update($validated);

        return redirect()->route('admin.offered-services.index')
            ->with('success', 'تم تحديث الخدمة بنجاح');
    }

    public function destroy(OfferedService $service)
    {
        $service->delete();

        return redirect()->route('admin.offered-services.index')
            ->with('success', 'تم حذف الخدمة بنجاح');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateService(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'nullable|string|max:255|unique:offered_services,slug';
        if ($ignoreId) {
            $slugRule .= ','.$ignoreId;
        }

        return Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'execution_duration' => 'nullable|string|max:255',
            'execution_days' => 'nullable|integer|min:0|max:3650',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ])->validate();
    }
}
