<?php

namespace App\Services\Mail;

use App\Models\MailTemplate;
use Illuminate\Support\Facades\Schema;

class MailTemplateResolver
{
    /**
     * @return array<string, array{name: string, subject: string, body_html: string, body_text: string, variables: array<int, string>}>
     */
    public function defaults(): array
    {
        return [
            'payment.received' => [
                'name' => 'استلام دفعة',
                'subject' => 'تم استلام دفعتك - {{invoice_number}}',
                'body_html' => '<p>مرحباً {{user_name}}،</p><p>تم استلام دفعتك بقيمة {{payment_amount}} على الفاتورة {{invoice_number}}.</p><p>المتبقي: {{balance}}</p>',
                'body_text' => 'مرحباً {{user_name}}، تم استلام دفعتك بقيمة {{payment_amount}} على الفاتورة {{invoice_number}}. المتبقي: {{balance}}',
                'variables' => ['user_name', 'invoice_number', 'payment_amount', 'balance', 'email', 'phone'],
            ],
            'auth.verify_email' => [
                'name' => 'التحقق من البريد',
                'subject' => 'تأكيد البريد الإلكتروني',
                'body_html' => '<p>مرحباً {{user_name}}</p><p>اضغط الرابط التالي لتأكيد بريدك:</p><p><a href="{{action_url}}">{{action_url}}</a></p>',
                'body_text' => 'مرحباً {{user_name}}، لتأكيد البريد: {{action_url}}',
                'variables' => ['user_name', 'email', 'action_url'],
            ],
            'auth.reset_password' => [
                'name' => 'إعادة تعيين كلمة المرور',
                'subject' => 'طلب إعادة تعيين كلمة المرور',
                'body_html' => '<p>مرحباً {{user_name}}</p><p>اضغط الرابط التالي لإعادة تعيين كلمة المرور:</p><p><a href="{{action_url}}">{{action_url}}</a></p><p>ينتهي الرابط خلال {{expire_minutes}} دقيقة.</p>',
                'body_text' => 'مرحباً {{user_name}}، رابط إعادة التعيين: {{action_url}} (صالح {{expire_minutes}} دقيقة).',
                'variables' => ['user_name', 'email', 'action_url', 'expire_minutes'],
            ],
            'backup.completed' => [
                'name' => 'اكتمال النسخ الاحتياطي',
                'subject' => 'اكتملت عملية النسخ الاحتياطي',
                'body_html' => '<p>تم إنشاء نسخة احتياطية بنجاح.</p><ul><li>الاسم: {{backup_name}}</li><li>النوع: {{backup_type}}</li><li>الحجم: {{backup_size}}</li><li>التاريخ: {{completed_at}}</li></ul>',
                'body_text' => 'تم إنشاء نسخة احتياطية بنجاح. الاسم: {{backup_name}} - النوع: {{backup_type}} - الحجم: {{backup_size}} - التاريخ: {{completed_at}}',
                'variables' => ['backup_name', 'backup_type', 'backup_size', 'completed_at'],
            ],
            'backup.failed' => [
                'name' => 'فشل النسخ الاحتياطي',
                'subject' => 'فشلت عملية النسخ الاحتياطي',
                'body_html' => '<p>فشلت عملية النسخ الاحتياطي.</p><ul><li>الاسم: {{backup_name}}</li><li>السبب: {{error_message}}</li></ul>',
                'body_text' => 'فشلت عملية النسخ الاحتياطي. الاسم: {{backup_name}} - السبب: {{error_message}}',
                'variables' => ['backup_name', 'error_message'],
            ],
            'coolify.ops_alert' => [
                'name' => 'تنبيه عمليات Coolify',
                'subject' => 'تنبيه Coolify - مركز العمليات',
                'body_html' => '<p>مرحباً،</p><p>تم رصد المشاكل التالية:</p><p>{{issues_html}}</p>',
                'body_text' => 'تنبيهات Coolify: {{issues_text}}',
                'variables' => ['issues_html', 'issues_text'],
            ],
        ];
    }

    public function ensureDefaults(): void
    {
        if (! Schema::hasTable('mail_templates')) {
            return;
        }

        foreach ($this->defaults() as $key => $template) {
            MailTemplate::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body_html' => $template['body_html'],
                    'body_text' => $template['body_text'],
                    'available_variables' => $template['variables'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array{subject: string, body_html: string, body_text: string}
     */
    public function resolve(string $key): array
    {
        if (Schema::hasTable('mail_templates')) {
            $template = MailTemplate::query()->where('key', $key)->where('is_active', true)->first();
            if ($template) {
                return [
                    'subject' => $template->subject,
                    'body_html' => $template->body_html,
                    'body_text' => $template->body_text ?? strip_tags($template->body_html),
                ];
            }
        }

        $default = $this->defaults()[$key] ?? [
            'subject' => $key,
            'body_html' => '',
            'body_text' => '',
        ];

        return [
            'subject' => $default['subject'],
            'body_html' => $default['body_html'],
            'body_text' => $default['body_text'],
        ];
    }
}
