<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIModel;
use App\Services\Ai\AIModelService;
use App\Services\Ai\GroqModelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AIModelController extends Controller
{
    public function __construct(
        private AIModelService $modelService,
        private GroqModelService $groqModelService
    ) {}

    /**
     * قائمة الموديلات
     */
    public function index()
    {
        $models = AIModel::with('creator')->latest()->paginate(20);
        $providers = AIModel::PROVIDERS;

        return view('admin.ai.models.index', compact('models', 'providers'));
    }

    /**
     * عرض نموذج إنشاء موديل
     */
    public function create()
    {
        $providers = AIModel::PROVIDERS;
        $capabilities = AIModel::CAPABILITIES;
        $supportedModels = AIModel::SUPPORTED_MODELS;

        return view('admin.ai.models.create', compact('providers', 'capabilities', 'supportedModels'));
    }

    /**
     * حفظ موديل جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:' . implode(',', array_keys(AIModel::PROVIDERS)),
            'model_key' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'api_endpoint' => 'nullable|string|max:500',
            'base_url' => 'nullable|url|max:500',
            'max_tokens' => 'required|integer|min:1|max:100000',
            'temperature' => 'required|numeric|min:0|max:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0',
            'cost_per_1k_tokens' => 'nullable|numeric|min:0',
            'capabilities' => 'required|array',
            'capabilities.*' => 'in:' . implode(',', array_keys(AIModel::CAPABILITIES)),
            'settings' => 'nullable|array',
        ], [
            'name.required' => 'اسم الموديل مطلوب',
            'provider.required' => 'المزود مطلوب',
            'model_key.required' => 'معرف الموديل مطلوب',
            'max_tokens.required' => 'الحد الأقصى للـ tokens مطلوب',
            'capabilities.required' => 'القدرات مطلوبة',
        ]);

        try {
            $validated['is_active'] = $request->has('is_active');
            $validated['is_default'] = $request->has('is_default');

            $model = $this->modelService->createModel($validated, Auth::user());

            return redirect()->route('admin.ai.models.index')
                           ->with('success', 'تم إنشاء الموديل بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error creating AI model: ' . $e->getMessage(), ['request' => $validated]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء إنشاء الموديل: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * عرض نموذج تعديل
     */
    public function edit(AIModel $model)
    {
        $providers = AIModel::PROVIDERS;
        $capabilities = AIModel::CAPABILITIES;
        $supportedModels = AIModel::SUPPORTED_MODELS;

        return view('admin.ai.models.edit', compact('model', 'providers', 'capabilities', 'supportedModels'));
    }

    /**
     * تحديث موديل
     */
    public function update(Request $request, AIModel $model)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:' . implode(',', array_keys(AIModel::PROVIDERS)),
            'model_key' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'api_endpoint' => 'nullable|string|max:500',
            'base_url' => 'nullable|url|max:500',
            'max_tokens' => 'required|integer|min:1|max:100000',
            'temperature' => 'required|numeric|min:0|max:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0',
            'cost_per_1k_tokens' => 'nullable|numeric|min:0',
            'capabilities' => 'required|array',
            'capabilities.*' => 'in:' . implode(',', array_keys(AIModel::CAPABILITIES)),
            'settings' => 'nullable|array',
        ]);

        try {
            $validated['is_active'] = $request->has('is_active');
            $validated['is_default'] = $request->has('is_default');

            // إذا لم يتم إدخال API key جديد أو كان فارغاً، لا نحدثه
            if (empty($validated['api_key']) || trim($validated['api_key']) === '') {
                unset($validated['api_key']);
            } else {
                // تأكد من أن api_key موجود وليس فارغاً
                $validated['api_key'] = trim($validated['api_key']);
                Log::info('API Key provided in update request', ['model_id' => $model->id, 'has_key' => !empty($validated['api_key'])]);
            }

            $this->modelService->updateModel($model, $validated);

            return redirect()->route('admin.ai.models.index')
                           ->with('success', 'تم تحديث الموديل بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating AI model: ' . $e->getMessage(), ['model_id' => $model->id]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحديث الموديل: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * حذف موديل
     */
    public function destroy(AIModel $model)
    {
        try {
            $this->modelService->deleteModel($model);

            return redirect()->route('admin.ai.models.index')
                           ->with('success', 'تم حذف الموديل بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error deleting AI model: ' . $e->getMessage(), ['model_id' => $model->id]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف الموديل.');
        }
    }

    /**
     * اختبار الموديل
     */
    public function test(AIModel $model)
    {
        try {
            $result = $this->modelService->testModel($model);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الاختبار: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * اختبار API Key مؤقت (قبل الحفظ)
     */
    public function testTemp(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:' . implode(',', array_keys(AIModel::PROVIDERS)),
            'model_key' => 'required|string|max:255',
            'api_key' => 'required|string',
            'base_url' => 'nullable|url|max:500',
            'api_endpoint' => 'nullable|string|max:500',
        ]);

        try {
            // استخدام method جديد للاختبار المؤقت مع API Key مباشر
            $modelData = [
                'provider' => $validated['provider'],
                'model_key' => $validated['model_key'],
                'base_url' => $validated['base_url'] ?? null,
                'api_endpoint' => $validated['api_endpoint'] ?? null,
                'max_tokens' => 100,
                'temperature' => 0.7,
                'is_active' => true,
            ];

            $result = $this->modelService->testModelWithRawApiKey($modelData, $validated['api_key']);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error testing temp API key: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الاختبار: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تعيين كموديل افتراضي
     */
    public function setDefault(AIModel $model)
    {
        try {
            $this->modelService->switchModel($model);

            return redirect()->back()
                           ->with('success', 'تم تعيين الموديل كافتراضي بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * جلب موديلات Groq ديناميكياً عبر AJAX
     */
    public function fetchGroqModels(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
        ], [
            'api_key.required' => 'مفتاح Groq API مطلوب لجلب الموديلات',
        ]);

        $result = $this->groqModelService->fetchAvailableModels($validated['api_key']);

        // إذا فشل الجلب من API، استخدم القائمة الثابتة
        if (!$result['success']) {
            $static = $this->groqModelService->getStaticModelsByProvider();

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'فشل في جلب الموديلات من Groq',
                'static_models' => $static,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'models' => $result['models'],
        ]);
    }

    /**
     * تفعيل/إلغاء تفعيل
     */
    public function toggleActive(AIModel $model)
    {
        try {
            $model->update(['is_active' => !$model->is_active]);

            return redirect()->back()
                           ->with('success', 'تم تحديث حالة الموديل بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
