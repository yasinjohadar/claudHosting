<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Services\Auth\PasswordCredentialDeliveryService;
use App\Services\Auth\PasswordResetMessageRenderer;
use App\Support\InternationalPhoneDigits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Set a customer's password from the customers list, and optionally send them the credentials.
 *
 * Separate from UserController::updatePassword, which redirects to the users index — from the
 * customers screen that throws the admin off the page they were working on. This answers JSON
 * so the modal can report the result in place.
 *
 * Nothing here ever logs the password.
 */
class CustomerPasswordController extends Controller
{
    /** How many suggestions the modal offers at once. */
    private const SUGGESTION_COUNT = 3;

    /**
     * Strong password suggestions.
     */
    public function suggest(PasswordCredentialDeliveryService $delivery): JsonResponse
    {
        $passwords = [];
        for ($i = 0; $i < self::SUGGESTION_COUNT; $i++) {
            // Same generator the automated credential flows already use, so a password an admin
            // picks here is no weaker than one the system would have produced itself.
            $passwords[] = $delivery->generateSecurePassword();
        }

        return response()->json(['success' => true, 'passwords' => $passwords]);
    }

    /**
     * What the customer would receive, rendered with the password about to be set.
     */
    public function preview(Request $request, User $user, PasswordResetMessageRenderer $renderer): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $phone = InternationalPhoneDigits::forUser($user);

        return response()->json([
            'success' => true,
            'text' => $renderer->renderCredentialWhatsApp($user, $validated['password']),
            'recipient' => $phone !== null ? InternationalPhoneDigits::toDisplay($phone) : null,
            'email' => $user->email,
            'template' => $this->credentialTemplateLabel(),
        ]);
    }

    /**
     * Set the password, then deliver the credentials if asked.
     */
    public function update(
        Request $request,
        User $user,
        PasswordCredentialDeliveryService $delivery,
    ): JsonResponse {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'notify_whatsapp' => ['nullable', 'boolean'],
            'notify_email' => ['nullable', 'boolean'],
        ], [
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $wantsWhatsApp = $request->boolean('notify_whatsapp');
        $wantsEmail = $request->boolean('notify_email');

        if ($wantsWhatsApp && InternationalPhoneDigits::forUser($user) === null) {
            return response()->json([
                // Refused before the password changes: telling the admin now beats saving a
                // password the customer will never be told about.
                'success' => false,
                'message' => 'لا يوجد رقم جوال صالح لهذا العميل، فلا يمكن إرسال البيانات عبر واتساب. أضف الرقم ورمز الدولة أولاً، أو ألغِ خيار الواتساب.',
            ], 422);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        $result = ['whatsapp_sent' => false, 'email_sent' => false];
        $deliveryError = null;

        if ($wantsWhatsApp || $wantsEmail) {
            try {
                $result = $delivery->deliver(
                    $user,
                    $validated['password'],
                    PasswordCredentialDeliveryService::CONTEXT_ADMIN_RESET,
                    // Only the channels the admin ticked. Without viaEmail/viaWhatsApp the
                    // service always attempts both, which would ignore an unticked box.
                    //
                    // Neither channel is marked "required": require* makes deliver() throw on
                    // the first failure, which here would mean a failed WhatsApp send also
                    // cancels the email. The password is already saved by this point, so there
                    // is nothing left to protect — trying both and reporting is strictly better
                    // for the customer.
                    viaEmail: $wantsEmail,
                    viaWhatsApp: $wantsWhatsApp,
                );
            } catch (\Throwable $e) {
                // The password IS already changed, so this is a partial success and has to be
                // said plainly — an admin who reads "failed" would set it again for nothing.
                $deliveryError = $e->getMessage();
                Log::channel('whatsapp')->warning('Password changed but credential delivery failed.', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // A channel that was asked for and did not go through is an error worth naming, even
        // though deliver() swallowed it into the result array.
        $deliveryError ??= $this->firstChannelError($result, $wantsWhatsApp, $wantsEmail);

        return response()->json([
            'success' => true,
            'password_changed' => true,
            'whatsapp_sent' => (bool) ($result['whatsapp_sent'] ?? false),
            'email_sent' => (bool) ($result['email_sent'] ?? false),
            'delivery_error' => $deliveryError,
            'message' => $this->buildMessage($user, $wantsWhatsApp || $wantsEmail, $result, $deliveryError),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function firstChannelError(array $result, bool $wantedWhatsApp, bool $wantedEmail): ?string
    {
        if ($wantedWhatsApp && ! ($result['whatsapp_sent'] ?? false) && ! empty($result['whatsapp_error'])) {
            return (string) $result['whatsapp_error'];
        }

        if ($wantedEmail && ! ($result['email_sent'] ?? false) && ! empty($result['email_error'])) {
            return (string) $result['email_error'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function buildMessage(User $user, bool $wantedDelivery, array $result, ?string $deliveryError): string
    {
        $name = $user->name ?: 'العميل';
        $message = 'تم تعيين كلمة مرور جديدة لـ'.$name.'.';

        if (! $wantedDelivery) {
            return $message.' لم تُرسل البيانات — انسخها وأرسلها بنفسك.';
        }

        $channels = [];
        if ($result['whatsapp_sent'] ?? false) {
            $channels[] = 'واتساب';
        }
        if ($result['email_sent'] ?? false) {
            $channels[] = 'البريد';
        }

        if ($channels !== []) {
            $message .= ' وأُرسلت البيانات عبر '.implode(' و', $channels).'.';
        }

        if ($deliveryError !== null) {
            $message .= ' لكن تعذّر الإرسال: '.$deliveryError
                .' كلمة المرور تغيّرت فعلاً — انسخها وأرسلها يدوياً بدل تغييرها من جديد.';
        } elseif ($channels === []) {
            $message .= ' لكن لم يُرسل شيء — راجع إعدادات الواتساب والبريد.';
        }

        return $message;
    }

    /** Which template the message will come from, for the modal's hint line. */
    private function credentialTemplateLabel(): ?string
    {
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return null;
        }

        try {
            return WhatsAppMessageTemplate::findBySlug(WhatsAppMessageTemplate::SLUG_CREDENTIALS)?->name;
        } catch (\Throwable) {
            return null;
        }
    }
}
