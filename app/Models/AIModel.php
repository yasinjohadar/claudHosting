<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class AIModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'name',
        'provider',
        'model_key',
        'api_key',
        'api_endpoint',
        'base_url',
        'max_tokens',
        'temperature',
        'is_active',
        'is_default',
        'priority',
        'cost_per_1k_tokens',
        'capabilities',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'max_tokens' => 'integer',
        'temperature' => 'float',
        'priority' => 'integer',
        'cost_per_1k_tokens' => 'float',
        'capabilities' => 'array',
        'settings' => 'array',
        // api_key يتم تشفيره يدوياً في setApiKeyAttribute
    ];
    
    /**
     * تخزين API Key مؤقت للاختبار (غير مشفر)
     */
    protected ?string $rawApiKey = null;
    
    /**
     * تعيين API Key مؤقت للاختبار
     */
    public function setRawApiKeyForTesting(string $apiKey): void
    {
        $this->rawApiKey = $apiKey;
    }
    
    /**
     * تشفير API Key عند الحفظ
     */
    public function setApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Providers available
     */
    public const PROVIDERS = [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic (Claude)',
        'google' => 'Google (Gemini)',
        'openrouter' => 'OpenRouter',
        'zai' => 'Z.ai (GLM)',
        'manus' => 'Manus AI',
        'groq' => 'Groq',
        'local' => 'Local LLM',
        'custom' => 'Custom API',
    ];

    /**
     * Capabilities
     */
    public const CAPABILITIES = [
        'blog_generation' => 'إنشاء مقالات المدونة',
        'content_summary' => 'تلخيص المحتوى',
        'content_improvement' => 'تحسين المحتوى',
        'chat' => 'محادثة',
        'translation' => 'ترجمة',
    ];

    /**
     * Supported models by provider
     */
    public const SUPPORTED_MODELS = [
        'openai' => [
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4o' => 'GPT-4o',
        ],
        'anthropic' => [
            'claude-3-opus' => 'Claude 3 Opus',
            'claude-3-sonnet' => 'Claude 3 Sonnet',
            'claude-3-haiku' => 'Claude 3 Haiku',
        ],
        'google' => [
            'gemini-pro' => 'Gemini Pro',
            'gemini-ultra' => 'Gemini Ultra',
        ],
        'openrouter' => [
            'openai/gpt-4' => 'GPT-4 (via OpenRouter)',
            'anthropic/claude-3-opus' => 'Claude 3 Opus (via OpenRouter)',
            'meta-llama/llama-3-70b-instruct' => 'Llama 3 70B (via OpenRouter)',
        ],
        'zai' => [
            'glm-4.7' => 'GLM-4.7',
            'glm-4' => 'GLM-4',
        ],
        'manus' => [
            // Manus AI models - يمكن إضافة الموديلات المحددة حسب وثائق Manus API
            // يمكن تركها فارغة للسماح بإدخال أي model_key
        ],
        'groq' => [
            // Groq models سيتم جلبها ديناميكياً من Groq API أو من GroqModelService
            // يمكن تركها فارغة للسماح باختيار أي model_key مدعوم من Groq
        ],
    ];

    /**
     * Get the creator of the model
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Get conversations using this model
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(AIConversation::class, 'ai_model_id');
    }

    /**
     * Get decrypted API key
     */
    public function getDecryptedApiKey(): ?string
    {
        // إذا كان هناك API Key مؤقت للاختبار، استخدمه
        if ($this->rawApiKey !== null) {
            return $this->rawApiKey;
        }
        
        // قراءة API Key من attributes مباشرة لتجنب casting
        $apiKey = $this->attributes['api_key'] ?? null;
        
        if (empty($apiKey)) {
            return null;
        }

        try {
            // محاولة فك التشفير
            return Crypt::decryptString($apiKey);
        } catch (\Exception $e) {
            // إذا فشل فك التشفير، قد يكون API Key غير مشفر
            return $apiKey;
        }
    }

    /**
     * Calculate cost for tokens
     */
    public function getCost(int $tokens): float
    {
        if (!$this->cost_per_1k_tokens || $this->cost_per_1k_tokens <= 0) {
            return 0;
        }

        return ($tokens / 1000) * $this->cost_per_1k_tokens;
    }

    /**
     * Check if model can handle a capability
     */
    public function canHandle(string $capability): bool
    {
        $capabilities = $this->capabilities ?? [];
        return in_array($capability, $capabilities);
    }

    /**
     * Scope a query to only include default models
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include active models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query by capability
     */
    public function scopeByCapability($query, string $capability)
    {
        return $query->whereJsonContains('capabilities', $capability);
    }
}
