<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTemplate;
use App\Services\Mail\MailTemplateResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MailTemplateController extends Controller
{
    public function __construct(
        protected MailTemplateResolver $resolver
    ) {}

    public function index(Request $request)
    {
        if (! Schema::hasTable('mail_templates')) {
            return redirect()
                ->route('admin.settings.email.index')
                ->with('error', 'جدول قوالب البريد غير موجود. يرجى تشغيل migrations أولاً.');
        }

        $this->resolver->ensureDefaults();

        $query = MailTemplate::query()->orderBy('name');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => MailTemplate::query()->count(),
            'active' => MailTemplate::query()->where('is_active', true)->count(),
            'inactive' => MailTemplate::query()->where('is_active', false)->count(),
        ];

        return view('admin.mail-templates.index', compact('templates', 'stats'));
    }

    public function create()
    {
        return view('admin.mail-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:120|unique:mail_templates,key',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'available_variables' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        MailTemplate::query()->create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? null,
            'available_variables' => $this->parseVariables($validated['available_variables'] ?? ''),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.mail-templates.index')->with('success', 'تم إنشاء القالب بنجاح.');
    }

    public function edit(MailTemplate $mailTemplate)
    {
        return view('admin.mail-templates.edit', compact('mailTemplate'));
    }

    public function update(Request $request, MailTemplate $mailTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'available_variables' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $mailTemplate->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? null,
            'available_variables' => $this->parseVariables($validated['available_variables'] ?? ''),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.mail-templates.index')->with('success', 'تم تحديث القالب بنجاح.');
    }

    public function destroy(MailTemplate $mailTemplate)
    {
        $mailTemplate->delete();

        return redirect()->route('admin.mail-templates.index')->with('success', 'تم حذف القالب.');
    }

    /**
     * @return array<int, string>
     */
    protected function parseVariables(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
