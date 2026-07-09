<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageTemplate extends Model
{
    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'name',
        'slug',
        'body',
        'type',
        'language',
        'meta_template_name',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public const TYPE_TEXT = 'text';
    public const TYPE_TEMPLATE = 'template';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get template body with variables replaced.
     *
     * @param array $replacements e.g. ['student_name' => 'أحمد', 'course_name' => 'البرمجة']
     * @return string
     */
    public function render(array $replacements = []): string
    {
        $body = $this->body;
        foreach ($replacements as $key => $value) {
            $body = str_replace(
                ['{{' . $key . '}}', '{' . $key . '}'],
                (string) $value,
                $body
            );
        }

        return self::normalizeBodyForSending($body);
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
}
