<?php

namespace App\Support;

/**
 * The catalogue of variables a WhatsApp message template may use.
 *
 * Single source of truth for two consumers that must never drift apart: the chips the admin
 * clicks to insert a variable, and the values substituted at send time. When those two lists
 * live in separate places, the UI eventually offers a variable that resolves to nothing.
 *
 * Deliberately NOT carried over from the old engine: {course_name} and {group_name}. They
 * were leftovers from a training-courses app and always resolved to an empty string, so
 * offering them would promise data this panel does not have.
 */
final class WhatsAppTemplateVariables
{
    public const GROUP_CUSTOMER = 'customer';

    public const GROUP_SUBSCRIPTION = 'subscription';

    public const GROUP_BILLING = 'billing';

    public const GROUP_SYSTEM = 'system';

    /**
     * Display metadata for each group, in the order the admin UI shows them.
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public static function groups(): array
    {
        return [
            self::GROUP_CUSTOMER => ['label' => 'بيانات العميل', 'icon' => 'fe fe-user'],
            self::GROUP_SUBSCRIPTION => ['label' => 'الاشتراك والاستضافة', 'icon' => 'fe fe-server'],
            self::GROUP_BILLING => ['label' => 'الفواتير والمدفوعات', 'icon' => 'fe fe-file-text'],
            self::GROUP_SYSTEM => ['label' => 'النظام والتاريخ', 'icon' => 'fe fe-settings'],
        ];
    }

    /**
     * Canonical variables.
     *
     * `sample` powers the preview, so it must look like real data — a preview full of
     * "value" placeholders tells the admin nothing about how the message will read.
     *
     * `aliases` keep older spellings working. Existing templates and the password-reset
     * settings body were written against them, so dropping them would silently blank out
     * parts of messages already in production.
     *
     * @return array<string, array{group: string, label: string, sample: string, aliases: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            // ---------- العميل ----------
            'customer_name' => [
                'group' => self::GROUP_CUSTOMER,
                'label' => 'اسم العميل',
                'sample' => 'أسامة عداس',
                'aliases' => ['student_name', 'user_name', 'customer_name_ar', 'customer_name_en', 'student_name_ar', 'student_name_en'],
            ],
            'customer_email' => [
                'group' => self::GROUP_CUSTOMER,
                'label' => 'بريد العميل',
                'sample' => 'eng.osama@example.com',
                'aliases' => ['student_email', 'email'],
            ],
            'customer_phone' => [
                'group' => self::GROUP_CUSTOMER,
                'label' => 'جوال العميل',
                'sample' => '+905519665883',
                'aliases' => ['phone'],
            ],
            'company_name' => [
                'group' => self::GROUP_CUSTOMER,
                'label' => 'اسم الشركة',
                'sample' => 'كلاودسوفت',
                'aliases' => [],
            ],
            'customer_city' => [
                'group' => self::GROUP_CUSTOMER,
                'label' => 'المدينة',
                'sample' => 'دمشق',
                'aliases' => [],
            ],
            'customer_country' => [
                'group' => self::GROUP_CUSTOMER,
                'label' => 'الدولة',
                'sample' => 'سوريا',
                'aliases' => [],
            ],

            // ---------- الاشتراك والاستضافة ----------
            'domain' => [
                'group' => self::GROUP_SUBSCRIPTION,
                'label' => 'النطاق',
                'sample' => 'example.com',
                'aliases' => ['account_domain'],
            ],
            'package' => [
                'group' => self::GROUP_SUBSCRIPTION,
                'label' => 'الباقة',
                'sample' => 'Business',
                'aliases' => [],
            ],
            'cpanel_username' => [
                'group' => self::GROUP_SUBSCRIPTION,
                'label' => 'مستخدم cPanel',
                'sample' => 'examplec',
                'aliases' => ['account_username'],
            ],
            'subscription_ends_at' => [
                'group' => self::GROUP_SUBSCRIPTION,
                'label' => 'تاريخ انتهاء الاشتراك',
                'sample' => '2027-03-15',
                'aliases' => [],
            ],
            'subscription_days_remaining' => [
                'group' => self::GROUP_SUBSCRIPTION,
                'label' => 'الأيام المتبقية',
                'sample' => '14',
                'aliases' => [],
            ],
            'subscription_status' => [
                'group' => self::GROUP_SUBSCRIPTION,
                'label' => 'حالة الاشتراك',
                'sample' => 'نشط',
                'aliases' => [],
            ],

            // ---------- الفواتير والمدفوعات ----------
            'invoice_number' => [
                'group' => self::GROUP_BILLING,
                'label' => 'رقم الفاتورة',
                'sample' => 'INV-2026-0142',
                'aliases' => [],
            ],
            'invoice_total' => [
                'group' => self::GROUP_BILLING,
                'label' => 'إجمالي الفاتورة',
                'sample' => '450.00',
                'aliases' => [],
            ],
            'invoice_balance' => [
                'group' => self::GROUP_BILLING,
                'label' => 'المتبقي على الفاتورة',
                'sample' => '150.00',
                'aliases' => [],
            ],
            'invoice_due_date' => [
                'group' => self::GROUP_BILLING,
                'label' => 'تاريخ الاستحقاق',
                'sample' => '2026-09-01',
                'aliases' => [],
            ],
            'invoice_status' => [
                'group' => self::GROUP_BILLING,
                'label' => 'حالة الفاتورة',
                'sample' => 'غير مدفوعة',
                'aliases' => [],
            ],
            'invoice_url' => [
                'group' => self::GROUP_BILLING,
                'label' => 'رابط الفاتورة',
                'sample' => 'https://example.com/client/invoices/142',
                'aliases' => [],
            ],
            'payment_amount' => [
                'group' => self::GROUP_BILLING,
                'label' => 'مبلغ الدفعة',
                'sample' => '300.00',
                'aliases' => [],
            ],

            // ---------- النظام والتاريخ ----------
            'app_name' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'اسم الموقع',
                'sample' => 'كلاودسوفت',
                'aliases' => [],
            ],
            'login_url' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'رابط تسجيل الدخول',
                'sample' => 'https://example.com/login',
                'aliases' => [],
            ],
            'support_url' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'رابط الدعم الفني',
                'sample' => 'https://example.com/client/tickets',
                'aliases' => [],
            ],
            'today' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'تاريخ اليوم',
                'sample' => '2026-08-19',
                'aliases' => [],
            ],
            'now' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'التاريخ والوقت الآن',
                'sample' => '2026-08-19 14:30',
                'aliases' => [],
            ],
            'code' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'رمز التحقق (OTP فقط)',
                'sample' => '482913',
                'aliases' => [],
            ],
            'password' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'كلمة المرور (قالب بيانات الدخول فقط)',
                // A fake on purpose: the preview must never show a real password, and this value
                // is only ever substituted from an explicit override — no model resolves it.
                'sample' => 'Xk7#mQ2p!vL9',
                'aliases' => ['new_password'],
            ],
            'admin_instructions' => [
                'group' => self::GROUP_SYSTEM,
                'label' => 'ملاحظات الإدارة',
                'sample' => 'يُنصح بتغيير كلمة المرور بعد أول دخول.',
                'aliases' => [],
            ],
        ];
    }

    /**
     * Canonical keys only — what the admin UI offers.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Canonical keys plus every alias — what save-time validation accepts.
     *
     * @return list<string>
     */
    public static function allKnownKeys(): array
    {
        $keys = [];
        foreach (self::definitions() as $key => $definition) {
            $keys[] = $key;
            foreach ($definition['aliases'] as $alias) {
                $keys[] = $alias;
            }
        }

        return array_values(array_unique($keys));
    }

    public static function isKnown(string $key): bool
    {
        return in_array($key, self::allKnownKeys(), true);
    }

    /**
     * Sample values for the preview, aliases included so a template written with an older
     * spelling previews just as well as a new one.
     *
     * @return array<string, string>
     */
    public static function sampleValues(): array
    {
        $samples = [];
        foreach (self::definitions() as $key => $definition) {
            $samples[$key] = $definition['sample'];
            foreach ($definition['aliases'] as $alias) {
                $samples[$alias] = $definition['sample'];
            }
        }

        return $samples;
    }

    /**
     * Canonical variables bucketed by group, for the insert-variable chips.
     *
     * @return array<string, array{label: string, icon: string, variables: list<array{key: string, label: string, sample: string}>}>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::groups() as $groupKey => $meta) {
            $grouped[$groupKey] = $meta + ['variables' => []];
        }

        foreach (self::definitions() as $key => $definition) {
            $group = $definition['group'];
            if (! isset($grouped[$group])) {
                continue;
            }

            $grouped[$group]['variables'][] = [
                'key' => $key,
                'label' => $definition['label'],
                'sample' => $definition['sample'],
            ];
        }

        return $grouped;
    }

    /**
     * The canonical key an alias points at, or the key itself when it is already canonical.
     */
    public static function canonical(string $key): ?string
    {
        $definitions = self::definitions();
        if (isset($definitions[$key])) {
            return $key;
        }

        foreach ($definitions as $canonical => $definition) {
            if (in_array($key, $definition['aliases'], true)) {
                return $canonical;
            }
        }

        return null;
    }
}
