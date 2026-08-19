<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppTemplateRenderer;
use App\Support\WhatsAppTemplateVariables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the WhatsApp message templates.
 *
 * The table and the model shipped long ago, but there was never a screen for them — the only
 * way to create a template was raw SQL. This is that screen.
 */
class WhatsAppMessageTemplateController extends Controller
{
    /** Templates whose flow requires a specific placeholder to be present. */
    private const REQUIRED_PLACEHOLDERS = [
        WhatsAppMessageTemplate::SLUG_OTP => 'code',
    ];

    public function index(Request $request)
    {
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return redirect()
                ->route('admin.whatsapp-messages.index')
                ->with('error', 'جدول قوالب الواتساب غير موجود. شغّل migrations أولاً: php artisan migrate');
        }

        $query = WhatsAppMessageTemplate::query()->orderByDesc('is_system')->orderBy('name');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->byCategory((string) $request->get('category'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => WhatsAppMessageTemplate::query()->count(),
            'active' => WhatsAppMessageTemplate::query()->where('is_active', true)->count(),
            'inactive' => WhatsAppMessageTemplate::query()->where('is_active', false)->count(),
            'system' => WhatsAppMessageTemplate::query()->where('is_system', true)->count(),
        ];

        return view('admin.pages.whatsapp-templates.index', [
            'templates' => $templates,
            'stats' => $stats,
            'categories' => WhatsAppMessageTemplate::categories(),
        ]);
    }

    public function create()
    {
        return view('admin.pages.whatsapp-templates.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $template = WhatsAppMessageTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => $validated['slug'] !== null && $validated['slug'] !== ''
                ? $validated['slug']
                : WhatsAppMessageTemplate::makeSlug($validated['name']),
            'body' => $validated['body'],
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => $validated['category'],
            'language' => 'ar',
            // Recorded from the body itself, so the list always reflects what the template
            // really uses rather than a stale hand-typed list.
            'variables' => WhatsAppTemplateRenderer::placeholdersIn($validated['body']),
            'is_active' => $request->boolean('is_active'),
            'is_system' => false,
        ]);

        return redirect()
            ->route('admin.whatsapp-templates.edit', $template)
            ->with('success', 'تم إنشاء القالب «'.$template->name.'».');
    }

    public function edit(WhatsAppMessageTemplate $whatsappTemplate)
    {
        return view('admin.pages.whatsapp-templates.edit', $this->formData($whatsappTemplate));
    }

    public function update(Request $request, WhatsAppMessageTemplate $whatsappTemplate): RedirectResponse
    {
        $validated = $this->validatePayload($request, $whatsappTemplate);

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'],
            'category' => $validated['category'],
            'variables' => WhatsAppTemplateRenderer::placeholdersIn($validated['body']),
            'is_active' => $request->boolean('is_active'),
        ];

        // The slug is the contract between the code and the row. Renaming a protected one
        // would leave the payment or OTP flow looking up a slug that no longer exists, and it
        // would fail silently, so the field is simply ignored for those.
        if (! $whatsappTemplate->isProtected()) {
            $attributes['slug'] = $validated['slug'] !== null && $validated['slug'] !== ''
                ? $validated['slug']
                : WhatsAppMessageTemplate::makeSlug($validated['name'], $whatsappTemplate->id);
        }

        $whatsappTemplate->update($attributes);

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', 'تم تحديث القالب «'.$whatsappTemplate->name.'».');
    }

    public function destroy(WhatsAppMessageTemplate $whatsappTemplate): RedirectResponse
    {
        if ($whatsappTemplate->isProtected()) {
            return back()->with(
                'error',
                'لا يمكن حذف «'.$whatsappTemplate->name.'» — النظام يستخدمه في إرسال تلقائي، وحذفه يوقف تلك الرسائل بلا إشعار. عطّله بدلاً من حذفه إن أردت إيقافه.'
            );
        }

        $name = $whatsappTemplate->name;
        $whatsappTemplate->delete();

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', 'تم حذف القالب «'.$name.'».');
    }

    /**
     * Live preview with the catalogue's sample values.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:8000'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $result = app(WhatsAppTemplateRenderer::class)->preview($validated['body']);
        $unknown = WhatsAppTemplateRenderer::unknownPlaceholdersIn($validated['body']);

        return response()->json([
            'success' => true,
            'text' => $result['text'],
            'length' => mb_strlen($result['text']),
            'used' => $result['used'],
            'unknown' => $unknown,
            'warning' => $unknown !== []
                ? 'متغيرات غير معروفة ستُحذف من الرسالة: '.implode('، ', array_map(static fn ($k) => '{'.$k.'}', $unknown))
                : null,
        ]);
    }

    /**
     * Send the rendered preview to one number, so the admin sees the real thing on a phone
     * before any customer does.
     */
    public function testSend(Request $request, SendWhatsAppMessage $sender): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:8000'],
            'to' => ['required', 'string', 'regex:/^\+?[1-9]\d{6,14}$/'],
        ], [
            'to.regex' => 'أدخل الرقم بصيغة دولية كاملة مثل +905519665883.',
        ]);

        $result = app(WhatsAppTemplateRenderer::class)->preview($validated['body']);

        if (trim($result['text']) === '') {
            return response()->json([
                'success' => false,
                'message' => 'نص القالب فارغ بعد التصيير — لا شيء لإرساله.',
            ], 422);
        }

        try {
            $sender->sendTextSync($validated['to'], $result['text'], false);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => EvolutionApiException::resolveUserMessage($e),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رسالة تجريبية إلى '.$validated['to'].' بالقيم النموذجية.',
        ]);
    }

    /**
     * @return array{name: string, description: ?string, slug: ?string, body: string, category: string}
     */
    private function validatePayload(Request $request, ?WhatsAppMessageTemplate $existing = null): array
    {
        $slugRule = 'unique:whatsapp_message_templates,slug'.($existing !== null ? ','.$existing->id : '');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/', $slugRule],
            // 4096 is WhatsApp's own limit for a text message; a longer body would be
            // rejected by the API after the admin thought it was saved and working.
            'body' => ['required', 'string', 'max:4096'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(WhatsAppMessageTemplate::categories()))],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'اسم القالب مطلوب.',
            'body.required' => 'نص القالب مطلوب.',
            'body.max' => 'حد رسالة الواتساب 4096 حرفاً.',
            'slug.regex' => 'المعرّف يقبل حروفاً إنجليزية صغيرة وأرقاماً وشرطة سفلية فقط.',
            'slug.unique' => 'هذا المعرّف مستخدم في قالب آخر.',
            'category.in' => 'التصنيف غير معروف.',
        ]);

        $this->assertPlaceholdersAreKnown($validated['body']);
        $this->assertRequiredPlaceholderPresent($validated['body'], $existing?->slug ?? ($validated['slug'] ?? null));

        return $validated + ['description' => null, 'slug' => null];
    }

    /**
     * Reject unknown placeholders at save time.
     *
     * The renderer also strips them when sending, but catching it here is what actually helps:
     * the admin learns about the typo while looking at the field, instead of a customer
     * receiving a sentence with a word missing.
     */
    private function assertPlaceholdersAreKnown(string $body): void
    {
        $unknown = WhatsAppTemplateRenderer::unknownPlaceholdersIn($body);
        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'body' => 'المتغيرات التالية غير معروفة وستُحذف من الرسالة: '
                .implode('، ', array_map(static fn ($key) => '{'.$key.'}', $unknown))
                .'. راجع قائمة المتغيرات المتاحة أعلى الحقل.',
        ]);
    }

    private function assertRequiredPlaceholderPresent(string $body, ?string $slug): void
    {
        $required = self::REQUIRED_PLACEHOLDERS[$slug] ?? null;
        if ($required === null) {
            return;
        }

        if (! in_array($required, WhatsAppTemplateRenderer::placeholdersIn($body), true)) {
            throw ValidationException::withMessages([
                // Without {code} the OTP message arrives with no code in it — a template that
                // saves cleanly and then breaks login for everyone.
                'body' => 'قالب رمز التحقق يجب أن يحتوي على {'.$required.'}، وإلا وصلت الرسالة بلا رمز.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?WhatsAppMessageTemplate $template = null): array
    {
        return [
            'template' => $template,
            'variableGroups' => WhatsAppTemplateVariables::grouped(),
            'categories' => WhatsAppMessageTemplate::categories(),
        ];
    }
}
