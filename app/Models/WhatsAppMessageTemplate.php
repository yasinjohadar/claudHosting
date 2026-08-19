<?php

namespace App\Models;

use App\Services\WhatsApp\WhatsAppTemplateRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WhatsAppMessageTemplate extends Model
{
    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'body',
        'type',
        'category',
        'language',
        'meta_template_name',
        'variables',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public const TYPE_TEXT = 'text';

    public const TYPE_TEMPLATE = 'template';

    /**
     * Slugs the code looks up directly. Losing one of these silently stops the flow that
     * depends on it, which is why rows carrying them are protected from deletion and rename.
     */
    public const SLUG_OTP = 'otp_code';

    public const SLUG_PAYMENT_RECEIVED = 'payment_received';

    public const SLUG_SUBSCRIPTION_EXPIRING = 'subscription_expiring';

    public const SLUG_AUTO_REPLY_FALLBACK = 'auto_reply_fallback';

    /** Login credentials sent to a customer after an admin sets their password. */
    public const SLUG_CREDENTIALS = 'credentials_delivery';

    /**
     * Categories, for grouping in the admin list.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return [
            'general' => 'عام',
            'marketing' => 'تسويق وإشعارات عامة',
            'billing' => 'الفواتير والمدفوعات',
            'subscription' => 'الاشتراك والاستضافة',
            'auth' => 'الدخول والتحقق',
            'support' => 'الدعم والردود',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? (string) $this->category;
    }

    /**
     * Slugs whose flow breaks if the row disappears or is renamed.
     *
     * @return list<string>
     */
    public static function protectedSlugs(): array
    {
        return [
            self::SLUG_OTP,
            self::SLUG_PAYMENT_RECEIVED,
            self::SLUG_SUBSCRIPTION_EXPIRING,
            self::SLUG_AUTO_REPLY_FALLBACK,
            self::SLUG_CREDENTIALS,
        ];
    }

    /** Both the flag and the slug list count, so a mis-seeded row is still protected. */
    public function isProtected(): bool
    {
        return (bool) $this->is_system
            || in_array((string) $this->slug, self::protectedSlugs(), true);
    }

    /**
     * Render the body with variables replaced.
     *
     * Delegates to WhatsAppTemplateRenderer, which resolves the shared variable catalogue,
     * honours the caller's own keys, and — the part that matters — strips any placeholder it
     * could not resolve. The previous implementation left unknown placeholders in the string,
     * so a single typo was delivered verbatim to a customer.
     *
     * @param  array<string, mixed>  $replacements  values the caller already knows
     * @param  array<string, mixed>  $context  models to resolve the catalogue against
     */
    public function render(array $replacements = [], array $context = []): string
    {
        return app(WhatsAppTemplateRenderer::class)->renderText(
            (string) $this->body,
            $context,
            $replacements,
            'template:'.($this->slug ?: $this->getKey()),
        );
    }

    /**
     * Render and report, for previews and for callers that need to know what went missing.
     *
     * @param  array<string, mixed>  $replacements
     * @param  array<string, mixed>  $context
     * @return array{text: string, unresolved: list<string>, used: list<string>}
     */
    public function renderDetailed(array $replacements = [], array $context = []): array
    {
        return app(WhatsAppTemplateRenderer::class)->render((string) $this->body, $context, $replacements);
    }

    /**
     * Convert stored HTML (from the editor) to WhatsApp-friendly plain text.
     */
    public static function normalizeBodyForSending(string $body): string
    {
        if ($body === '' || strip_tags($body) === $body) {
            return $body;
        }

        $html = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $html = preg_replace('/<\/div>/i', "\n", $html);
        $html = preg_replace('/<\/li>/i', "\n", $html);
        $html = preg_replace('/<li[^>]*>/i', '• ', $html);
        $html = preg_replace('/<(strong|b)[^>]*>(.*?)<\/\1>/is', '*$2*', $html);
        $html = preg_replace('/<(em|i)[^>]*>(.*?)<\/\1>/is', '_$2_', $html);
        $html = preg_replace('/<(s|strike|del)[^>]*>(.*?)<\/\1>/is', '~$2~', $html);

        $text = strip_tags($html);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Find by slug (for programmatic use).
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::active()->where('slug', $slug)->first();
    }

    /**
     * Slug for a name, kept unique. Arabic names transliterate to nothing, so a stable
     * fallback is needed rather than an empty slug that would collide with the next one.
     */
    public static function makeSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source, '_');
        if ($base === '') {
            $base = 'template';
        }

        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'_'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
