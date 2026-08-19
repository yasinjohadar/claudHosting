<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppTemplateRenderer;
use App\Support\InternationalPhoneDigits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Send a WhatsApp message to one customer from the customers list.
 *
 * Previews render against the customer's REAL data rather than the catalogue's sample values:
 * the admin is about to message this specific person, so seeing "أسامة عداس" beats seeing a
 * placeholder — and it surfaces a variable that happens to be empty for this customer before
 * the message goes out rather than after.
 */
class CustomerWhatsAppController extends Controller
{
    /**
     * Active text templates, for the picker in the modal.
     */
    public function templates(): JsonResponse
    {
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return response()->json([
                'success' => true,
                'templates' => [],
                'message' => 'جدول القوالب غير موجود. شغّل: php artisan migrate',
            ]);
        }

        $templates = WhatsAppMessageTemplate::active()
            ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'category'])
            ->map(fn (WhatsAppMessageTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->categoryLabel(),
            ])
            ->values();

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    /**
     * Render either a chosen template or free text against this customer.
     */
    public function preview(Request $request, User $user): JsonResponse
    {
        $validated = $this->validatePayload($request);

        [$body, $error] = $this->resolveBody($validated);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $result = app(WhatsAppTemplateRenderer::class)->render($body, $this->contextFor($user));
        $phone = InternationalPhoneDigits::forUser($user);

        return response()->json([
            'success' => true,
            'text' => $result['text'],
            'length' => mb_strlen($result['text']),
            'recipient' => $phone !== null ? InternationalPhoneDigits::toDisplay($phone) : null,
            'unresolved' => $result['unresolved'],
            // Named explicitly: an admin who sees which variables came out empty can pick a
            // different template instead of sending a sentence with a gap in it.
            'warning' => $result['unresolved'] !== []
                ? 'لا توجد بيانات لهذه المتغيرات عند هذا العميل، وستُحذف من الرسالة: '
                    .implode('، ', array_map(static fn ($k) => '{'.$k.'}', $result['unresolved']))
                : null,
        ]);
    }

    public function send(Request $request, User $user, SendWhatsAppMessage $sender): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $phone = InternationalPhoneDigits::forUser($user);
        if ($phone === null) {
            return response()->json([
                'success' => false,
                // The stored number needs a country code to be dialable; saying so beats a
                // provider-level failure the admin cannot interpret.
                'message' => 'لا يوجد رقم جوال صالح لهذا العميل. أضف الرقم ورمز الدولة من صفحة تعديل العميل.',
            ], 422);
        }

        [$body, $error] = $this->resolveBody($validated);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $result = app(WhatsAppTemplateRenderer::class)->render($body, $this->contextFor($user));
        $text = trim($result['text']);

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'الرسالة فارغة بعد تعبئة المتغيرات — راجع القالب أو اكتب نصاً.',
            ], 422);
        }

        try {
            $sender->sendTextSync(InternationalPhoneDigits::toDisplay($phone), $text, false);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Sending a WhatsApp message to a customer failed.', [
                'user_id' => $user->id,
                'template_id' => $validated['template_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => EvolutionApiException::resolveUserMessage($e),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة إلى '.($user->name ?: 'العميل').' على '.InternationalPhoneDigits::toDisplay($phone).'.',
        ]);
    }

    /**
     * @return array{template_id: ?int, message: ?string}
     */
    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'template_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:4096'],
        ], [
            'message.max' => 'حد رسالة الواتساب 4096 حرفاً.',
        ]);

        return [
            'template_id' => isset($validated['template_id']) ? (int) $validated['template_id'] : null,
            'message' => $validated['message'] ?? null,
        ];
    }

    /**
     * The raw body to render: a template's text, or what the admin typed.
     *
     * @param  array{template_id: ?int, message: ?string}  $validated
     * @return array{0: string, 1: ?string} [body, error]
     */
    private function resolveBody(array $validated): array
    {
        if ($validated['template_id'] !== null) {
            $template = WhatsAppMessageTemplate::active()
                ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
                ->find($validated['template_id']);

            if ($template === null) {
                return ['', 'القالب المحدد غير موجود أو غير مفعّل.'];
            }

            return [(string) $template->body, null];
        }

        $message = trim((string) $validated['message']);

        return $message === ''
            ? ['', 'اختر قالباً أو اكتب نص الرسالة.']
            : [$message, null];
    }

    /**
     * @return array<string, mixed>
     */
    private function contextFor(User $user): array
    {
        return [
            'user' => $user,
            // Optional context is loaded defensively: a template that mentions no subscription
            // must not fail to send because a related table could not be read. Anything that
            // stays null simply shows up in `unresolved`.
            'customer' => $this->quietly(fn () => $user->customer),
            'whmAccount' => $this->quietly(fn () => $user->whmAccounts()
                ->where('status', '!=', 'terminated')
                // Newest expiry first, so subscription variables describe the live service the
                // admin is most likely writing about.
                ->orderByDesc('subscription_ends_at')
                ->first()),
        ];
    }

    private function quietly(callable $resolver): mixed
    {
        try {
            return $resolver();
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Could not load context for a customer WhatsApp message.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
