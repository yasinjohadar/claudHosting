<?php

namespace App\Services\WhatsApp\AutoReply;

class WhatsAppAutoReplyPromptBuilder
{
    public function buildSystemPrompt(array $settings): string
    {
        $custom = trim($settings['auto_reply_ai_system_prompt'] ?? '');
        $faq = trim($settings['auto_reply_faq_context'] ?? '');

        if ($custom !== '') {
            $base = $custom;
        } else {
            $base = implode("\n", [
                'أنت مساعد أكاديمية كلاودسوفت على WhatsApp.',
                '- أجب بالعربية الفصحى المبسّطة، بأسلوب مهني وودود.',
                '- نظّم الرد: ترحيب قصير → جواب مباشر → خطوة تالية إن لزم.',
                '- لا تطلب ولا تفترض بيانات شخصية (اسم، فاتورة، كورس محدد).',
                '- 2–4 جمل كحد أقصى للرد الكامل.',
                '- لا markdown ولا قوائم طويلة.',
            ]);

            if ($faq === '') {
                $base .= "\n".implode("\n", [
                    '- لا توجد قائمة أسئلة شائعة مُعرَّفة حالياً.',
                    '- أجب على سؤال الطالب مباشرةً باستخدام معرفتك العامة عن المنصات التعليمية والدعم الفني.',
                    '- إن السؤال يتطلب بيانات حساب أو إجراء إداري: وجّه بلطف للتواصل مع فريق الدعم.',
                ]);
            } else {
                $base .= "\n- اعتمد أولاً على قسم FAQ أدناه، ثم أكمل بإيجاز إن لزم.";
            }
        }

        if ($faq !== '') {
            $base .= "\n\n=== FAQ ===\n".$faq;
        }

        return $base;
    }

    /**
     * @param  string[]  $incomingMessages
     * @return array<int, array{role: string, content: string}>
     */
    public function buildChatMessages(array $settings, array $incomingMessages): array
    {
        $combined = implode("\n", array_filter(array_map('trim', $incomingMessages)));
        if ($combined === '') {
            $combined = 'مرحباً';
        }

        return [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($settings)],
            ['role' => 'user', 'content' => $combined],
        ];
    }
}
