<?php

namespace Database\Seeders;

use App\Models\WhatsAppMessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the templates the code looks up by slug.
 *
 * Each body reproduces the text that flow sends TODAY, so installing this changes nothing
 * that is visible to a customer — it only moves the wording out of the code and into a row
 * the admin can edit. firstOrCreate, never update: re-running must not overwrite wording the
 * admin has since customised.
 */
class WhatsAppMessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            WhatsAppMessageTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                $template + [
                    'type' => WhatsAppMessageTemplate::TYPE_TEXT,
                    'language' => 'ar',
                    'is_active' => true,
                    'is_system' => true,
                ]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'slug' => WhatsAppMessageTemplate::SLUG_OTP,
                'name' => 'رمز التحقق (OTP)',
                'description' => 'يُرسل عند تسجيل الدخول أو استعادة كلمة المرور. يجب أن يحتوي على {code}.',
                'category' => 'auth',
                // Identical to the current default in PhoneOtpSettingsService.
                'body' => 'رمز التحقق الخاص بك هو: {code}',
                'variables' => ['code', 'app_name', 'customer_name'],
            ],
            [
                'slug' => WhatsAppMessageTemplate::SLUG_PAYMENT_RECEIVED,
                'name' => 'إشعار استلام دفعة',
                'description' => 'يُرسل للعميل تلقائياً عند تسجيل دفعة على فاتورته.',
                'category' => 'billing',
                // Word for word the sprintf() text in SendPaymentWhatsappListener, so the
                // message a customer receives does not change on the day this is installed.
                'body' => 'تم استلام دفعتك بقيمة {payment_amount} على الفاتورة رقم {invoice_number}. المتبقي: {invoice_balance}. يمكنك مراجعة الفاتورة من بوابة العميل.',
                'variables' => ['payment_amount', 'invoice_number', 'invoice_balance', 'invoice_url', 'customer_name'],
            ],
            [
                'slug' => WhatsAppMessageTemplate::SLUG_SUBSCRIPTION_EXPIRING,
                'name' => 'تنبيه قرب انتهاء الاشتراك',
                'description' => 'يُرسل قبل انتهاء اشتراك الاستضافة بعدد الأيام المضبوط.',
                'category' => 'subscription',
                'body' => "مرحباً {customer_name}،\nاشتراك استضافة {domain} ينتهي بعد {subscription_days_remaining} يوم (بتاريخ {subscription_ends_at}).\nللتجديد وتفادي إيقاف الخدمة: {login_url}",
                'variables' => ['customer_name', 'domain', 'subscription_days_remaining', 'subscription_ends_at', 'package', 'login_url'],
            ],
            [
                'slug' => WhatsAppMessageTemplate::SLUG_AUTO_REPLY_FALLBACK,
                'name' => 'رد تلقائي احتياطي',
                'description' => 'يُستخدم حين لا يُنتج الرد التلقائي جواباً.',
                'category' => 'support',
                'body' => "شكراً لتواصلك مع {app_name}. وصلت رسالتك وسيتواصل معك فريق الدعم قريباً.\nيمكنك أيضاً فتح تذكرة من: {support_url}",
                'variables' => ['app_name', 'support_url', 'customer_name'],
            ],
            [
                'slug' => 'welcome_new_account',
                'name' => 'ترحيب بحساب استضافة جديد',
                'description' => 'قالب جاهز للإرسال اليدوي بعد تجهيز حساب استضافة.',
                'category' => 'subscription',
                'body' => "مرحباً {customer_name}،\nتم تجهيز حساب استضافتك على {domain} بباقة {package}.\nاسم المستخدم: {cpanel_username}\nلوحة التحكم: {login_url}\n\nفريق {app_name}",
                'variables' => ['customer_name', 'domain', 'package', 'cpanel_username', 'login_url', 'app_name'],
                // Not looked up by slug anywhere, so the admin may delete it freely.
                'is_system' => false,
            ],
        ];
    }
}
