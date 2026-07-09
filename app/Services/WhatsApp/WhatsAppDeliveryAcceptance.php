<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessage;

/**
 * Decide whether an outbound WhatsAppMessage should be treated as successfully accepted by the provider.
 */
final class WhatsAppDeliveryAcceptance
{
    /**
     * @var list<string>
     */
    private const ACCEPTED_STATUSES = [
        WhatsAppMessage::STATUS_SENT,
        WhatsAppMessage::STATUS_DELIVERED,
        WhatsAppMessage::STATUS_READ,
    ];

    public static function isAccepted(?WhatsAppMessage $message): bool
    {
        if ($message === null) {
            return false;
        }

        if (! in_array($message->status, self::ACCEPTED_STATUSES, true)) {
            return false;
        }

        $metaId = trim((string) ($message->meta_message_id ?? ''));
        if ($metaId === '') {
            return false;
        }

        // Reject placeholder IDs generated on incomplete responses.
        if (str_starts_with($metaId, 'evo_') && strlen($metaId) < 20) {
            $response = $message->payload['response'] ?? null;
            if (! is_array($response) || $response === []) {
                return false;
            }
        }

        return true;
    }

    public static function rejectionReason(?WhatsAppMessage $message): string
    {
        if ($message === null) {
            return 'لم يُرجع مزوّد الواتساب أي رسالة.';
        }

        if ($message->status === WhatsAppMessage::STATUS_FAILED) {
            $error = $message->error['message'] ?? null;

            return is_string($error) && $error !== ''
                ? $error
                : 'فشل إرسال رسالة الواتساب.';
        }

        if ($message->status === WhatsAppMessage::STATUS_QUEUED) {
            return 'رسالة الواتساب بقيت في قائمة الانتظار دون تأكيد الإرسال.';
        }

        if (trim((string) ($message->meta_message_id ?? '')) === '') {
            return 'لم يؤكّد مزوّد الواتساب معرف الرسالة.';
        }

        return 'تعذّر تأكيد قبول رسالة الواتساب لدى المزود.';
    }
}
