<?php

namespace App\Support;

use App\Models\User;

/**
 * How complete a client profile is, and what is still missing.
 *
 * One source of truth because the figure is shown in more than one place (the profile page
 * and the dashboard); the previous inline calculation lived in a Blade template and would
 * have drifted the moment it was copied. It also ignored name and email entirely, so an
 * account that had both still read "0%" — which says "you have done nothing" to someone who
 * has in fact registered.
 *
 * The percentage counts REQUIRED fields only. Optional ones are listed separately and never
 * hold the bar below 100%, so an individual with no company name is not stuck at 85% for
 * ever with no way to finish.
 */
final class ProfileCompletion
{
    /** @var list<array{key: string, label: string, why: string, icon: string, filled: bool}> */
    private array $required = [];

    /** @var list<array{key: string, label: string, why: string, icon: string, filled: bool}> */
    private array $optional = [];

    public function __construct(private User $user)
    {
        foreach (self::definitions() as $definition) {
            $item = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'why' => $definition['why'],
                'icon' => $definition['icon'],
                'filled' => $this->isFilled($definition['key']),
            ];

            if ($definition['required']) {
                $this->required[] = $item;
            } else {
                $this->optional[] = $item;
            }
        }
    }

    public static function for(User $user): self
    {
        return new self($user);
    }

    /**
     * Field catalogue. `why` is deliberately concrete: a generic "improve your experience"
     * gives nobody a reason to act, whereas "this is how your password reset reaches you"
     * does — and for the phone that is literally true.
     *
     * @return list<array{key: string, label: string, why: string, icon: string, required: bool}>
     */
    private static function definitions(): array
    {
        return [
            [
                'key' => 'name',
                'label' => 'الاسم الكامل',
                'why' => 'يظهر على فواتيرك وتذاكر الدعم.',
                'icon' => 'fe fe-user',
                'required' => true,
            ],
            [
                'key' => 'email',
                'label' => 'البريد الإلكتروني',
                'why' => 'إليه تُرسل الفواتير وتنبيهات انتهاء الاشتراك.',
                'icon' => 'fe fe-mail',
                'required' => true,
            ],
            [
                'key' => 'phone',
                'label' => 'رقم الجوال / واتساب',
                'why' => 'قناة استعادة كلمة المرور عبر واتساب، وأسرع طريقة لتنبيهك قبل انتهاء الاشتراك.',
                'icon' => 'fe fe-phone',
                'required' => true,
            ],
            [
                'key' => 'country',
                'label' => 'الدولة',
                'why' => 'تُحدَّد بها بيانات الفاتورة وطريقة الدفع المتاحة.',
                'icon' => 'fe fe-flag',
                'required' => true,
            ],
            [
                'key' => 'city',
                'label' => 'المدينة',
                'why' => 'تكتمل بها بيانات الفاتورة الرسمية.',
                'icon' => 'fe fe-map-pin',
                'required' => true,
            ],
            [
                'key' => 'address1',
                'label' => 'العنوان',
                'why' => 'مطلوب على الفاتورة الرسمية.',
                'icon' => 'fe fe-map',
                'required' => true,
            ],
            [
                'key' => 'photo',
                'label' => 'الصورة الشخصية',
                'why' => 'تميّز حسابك في لوحة التحكم وتذاكر الدعم.',
                'icon' => 'fe fe-camera',
                'required' => false,
            ],
            [
                'key' => 'username',
                'label' => 'اسم المستخدم',
                'why' => 'اسم قصير بديل للبريد عند تسجيل الدخول.',
                'icon' => 'fe fe-at-sign',
                'required' => false,
            ],
            [
                'key' => 'companyname',
                'label' => 'اسم الشركة',
                'why' => 'يُدرج على الفاتورة إن كان الحساب لشركة.',
                'icon' => 'fe fe-briefcase',
                'required' => false,
            ],
        ];
    }

    /**
     * A phone counts only with its dial code: the number alone cannot be dialled or used
     * for WhatsApp delivery, so treating it as done would be a lie.
     */
    private function isFilled(string $key): bool
    {
        if ($key === 'phone') {
            return trim((string) $this->user->phone) !== ''
                && trim((string) $this->user->country_code) !== '';
        }

        if ($key === 'photo') {
            return trim((string) $this->user->photo) !== '';
        }

        return trim((string) ($this->user->{$key} ?? '')) !== '';
    }

    public function percent(): int
    {
        $total = count($this->required);
        if ($total === 0) {
            return 100;
        }

        return (int) round($this->completedCount() / $total * 100);
    }

    public function completedCount(): int
    {
        return count(array_filter($this->required, static fn (array $item): bool => $item['filled']));
    }

    public function totalCount(): int
    {
        return count($this->required);
    }

    public function isComplete(): bool
    {
        return $this->missing() === [];
    }

    /** @return list<array{key: string, label: string, why: string, icon: string, filled: bool}> */
    public function missing(): array
    {
        return array_values(array_filter($this->required, static fn (array $item): bool => ! $item['filled']));
    }

    /** @return list<array{key: string, label: string, why: string, icon: string, filled: bool}> */
    public function done(): array
    {
        return array_values(array_filter($this->required, static fn (array $item): bool => $item['filled']));
    }

    /** @return list<array{key: string, label: string, why: string, icon: string, filled: bool}> */
    public function optionalMissing(): array
    {
        return array_values(array_filter($this->optional, static fn (array $item): bool => ! $item['filled']));
    }

    /**
     * Tone for the bar, so colour and message can never disagree.
     *
     * Red starts below 25% only. A freshly registered account already sits at 33% (name and
     * email), and painting that red reads as "something is broken" rather than "there is
     * more to add".
     */
    public function tone(): string
    {
        return match (true) {
            $this->isComplete() => 'success',
            $this->percent() >= 60 => 'primary',
            $this->percent() >= 25 => 'warning',
            default => 'danger',
        };
    }

    public function headline(): string
    {
        return match (true) {
            $this->isComplete() => 'ملفك مكتمل',
            $this->percent() >= 60 => 'بقيت خطوة أو خطوتان',
            default => 'ملفك الشخصي غير مكتمل',
        };
    }
}
