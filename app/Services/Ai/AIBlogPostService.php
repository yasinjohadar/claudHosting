<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\Log;
use App\Services\Ai\AIProviderFactory;
use Illuminate\Support\Str;

class AIBlogPostService
{
    public function __construct(
        private AIModelService $modelService
    ) {}

    /**
     * توليد مقال كامل بالذكاء الاصطناعي
     */
    public function generateBlogPost(
        string $topic,
        AIModel $model,
        array $options = []
    ): array {
        $contentLength = $options['content_length'] ?? 'medium';
        $tone = $options['tone'] ?? 'professional';
        $language = $options['language'] ?? 'ar';
        $category = $options['category'] ?? null;

        // زيادة وقت التنفيذ إلى 500 ثانية لتوليد المقالات الطويلة
        set_time_limit(500);

        try {
            // توليد المحتوى الرئيسي
            $contentData = $this->generateContent($topic, $model, $contentLength, $tone, $language, $category);
            
            $title = $contentData['title'] ?? $topic;
            $content = $contentData['content'] ?? '';
            $excerpt = $contentData['excerpt'] ?? $this->generateExcerpt($content, $language);
            $slug = $this->generateSlug($title);

            $result = [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
            ];

            // توليد حقول SEO إذا كانت مفعلة
            if ($options['generate_seo'] ?? true) {
                $seoData = $this->generateSEOFields($title, $content, $topic, $model, $language);
                $result = array_merge($result, $seoData);
            }

            // توليد Open Graph إذا كان مفعلاً
            if ($options['generate_og'] ?? true) {
                $ogData = $this->generateOpenGraph($title, $content, $excerpt, $model, $language);
                $result = array_merge($result, $ogData);
            }

            // توليد Twitter Card إذا كان مفعلاً
            if ($options['generate_twitter'] ?? true) {
                $twitterData = $this->generateTwitterCard($title, $content, $excerpt, $model, $language);
                $result = array_merge($result, $twitterData);
            }

            // توليد Schema.org إذا كان مفعلاً
            if ($options['generate_schema'] ?? true) {
                $schemaData = $this->generateSchema($title, $content, $excerpt, $model, $language);
                $result = array_merge($result, $schemaData);
            }

            // توليد Focus Keyword Synonyms إذا كان مفعلاً
            if ($options['generate_keyword_synonyms'] ?? true && isset($result['focus_keyword'])) {
                $synonyms = $this->generateKeywordSynonyms($result['focus_keyword'], $model, $language);
                $result['focus_keyword_synonyms'] = $synonyms;
            }

            // Canonical URL
            $result['canonical_url'] = url('/blog/' . $slug);

            // Reading time
            $wordCount = str_word_count(strip_tags($content));
            $result['reading_time'] = max(1, ceil($wordCount / 200));

            return $result;

        } catch (\Exception $e) {
            Log::error('Error generating blog post: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'topic' => $topic,
                'model' => $model->name ?? 'unknown',
                'options' => $options,
            ]);
            
            // تحسين رسالة الخطأ
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Timeout') !== false) {
                throw new \Exception('انتهت مهلة الاتصال. يرجى المحاولة مرة أخرى أو تقليل طول المحتوى المطلوب.');
            }
            
            throw $e;
        }
    }

    /**
     * توليد المحتوى الرئيسي
     */
    private function generateContent(
        string $topic,
        AIModel $model,
        string $contentLength,
        string $tone,
        string $language,
        ?BlogCategory $category
    ): array {
        $lengthMap = [
            'short' => '500-800 كلمة',
            'medium' => '1000-1500 كلمة',
            'long' => '2000-3000 كلمة',
        ];

        $toneMap = [
            'professional' => 'احترافي ومهني',
            'friendly' => 'ودود وسهل',
            'technical' => 'تقني ومفصل',
            'casual' => 'عادي ومرن',
            'formal' => 'رسمي ومهذب',
        ];

        $categoryContext = $category ? "التصنيف: {$category->name}. " : '';

        $prompt = "أنت كاتب محترف للمدونات. اكتب مقالاً شاملاً ومتكاملاً باللغة العربية حول الموضوع التالي:

الموضوع: {$topic}
{$categoryContext}
الطول المطلوب: {$lengthMap[$contentLength]}
الأسلوب: {$toneMap[$tone]}

المتطلبات:
1. اكتب عنوان جذاب ومحسن لـ SEO (50-60 حرف)
2. اكتب محتوى شامل ومنظم مع:
   - مقدمة جذابة
   - فقرات منظمة مع عناوين فرعية
   - معلومات قيمة ومفيدة
   - خاتمة تلخص النقاط الرئيسية
3. استخدم HTML tags مناسبة (h2, h3, p, ul, ol, strong, em)
4. أضف أمثلة عملية عند الحاجة
5. استخدم لغة عربية فصيحة وسليمة

يرجى إرجاع النتيجة بصيغة JSON بالشكل التالي:
{
    \"title\": \"عنوان المقال\",
    \"content\": \"المحتوى الكامل مع HTML tags\",
    \"excerpt\": \"مقتطف قصير من المقال (100-150 كلمة)\"
}";

        try {
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => $model->max_tokens ?? 4000,
                'temperature' => $model->temperature ?? 0.7,
            ]);

            // التحقق من أن الاستجابة ليست فارغة
            if (empty($response)) {
                Log::warning('Empty response from AI provider', [
                    'topic' => $topic,
                    'model' => $model->name,
                ]);
                throw new \Exception('لم يتم استلام استجابة من موديل AI. يرجى المحاولة مرة أخرى.');
            }

            // Parse JSON response
            $data = $this->parseJSONResponse($response);
            
            if (!isset($data['title']) || !isset($data['content'])) {
                // Fallback: use topic as title and response as content
                Log::info('Using fallback for content generation', [
                    'topic' => $topic,
                    'response_length' => strlen($response),
                ]);
                $data = [
                    'title' => $topic,
                    'content' => $response,
                    'excerpt' => $this->generateExcerpt($response, $language),
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error in generateContent: ' . $e->getMessage(), [
                'topic' => $topic,
                'model' => $model->name ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('خطأ في توليد المحتوى: ' . $e->getMessage());
        }
    }

    /**
     * توليد جميع حقول SEO
     */
    private function generateSEOFields(
        string $title,
        string $content,
        string $topic,
        AIModel $model,
        string $language
    ): array {
        $prompt = "أنت خبير SEO محترف. قم بإنشاء حقول SEO محسنة بدقة للمقال التالي:

العنوان: {$title}
الموضوع: {$topic}

المتطلبات:
1. Meta Title: 50-60 حرف بالضبط، جذاب ويحتوي على الكلمة المفتاحية الرئيسية، بدون رموز غريبة
2. Meta Description: 150-160 حرف بالضبط، وصف جذاب ومشوق للمقال، بدون رموز غريبة
3. Meta Keywords: 8-12 كلمة مفتاحية ذات صلة قوية بالموضوع، مفصولة بفواصل فقط، بدون أرقام أو رموز، كل كلمة كاملة وصحيحة
4. Focus Keyword: كلمة مفتاحية رئيسية واحدة أو عبارة قصيرة (2-4 كلمات)، بدون رموز أو علامات

مهم جداً:
- استخدم فقط الكلمات العربية والإنجليزية الصحيحة والكاملة
- لا تستخدم أي رموز غريبة مثل ? أو * أو [ أو ]
- لا تستخدم كلمات مكسورة أو غير مكتملة
- تأكد من أن جميع الكلمات واضحة ومفهومة

يرجى إرجاع النتيجة بصيغة JSON فقط بدون أي نص إضافي:
{
    \"meta_title\": \"عنوان SEO\",
    \"meta_description\": \"وصف SEO\",
    \"meta_keywords\": \"كلمة1, كلمة2, كلمة3\",
    \"focus_keyword\": \"الكلمة المفتاحية الرئيسية\"
}";

        try {
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => 500,
                'temperature' => 0.5,
            ]);

            $data = $this->parseJSONResponse($response);

            // تنظيف البيانات قبل الإرجاع
            $metaKeywords = $data['meta_keywords'] ?? $this->extractKeywords($content);
            $focusKeyword = $data['focus_keyword'] ?? $this->extractMainKeyword($topic, $content);
            
            // تنظيف الكلمات المفتاحية
            $metaKeywords = $this->cleanKeywords($metaKeywords);
            $focusKeyword = trim(preg_replace('/[^\p{Arabic}\p{L}\p{N}\s-]/u', '', $focusKeyword));
            $focusKeyword = preg_replace('/\s+/u', ' ', $focusKeyword);
            
            // Fallbacks مع تحسين
            return [
                'meta_title' => $this->cleanText($data['meta_title'] ?? Str::limit($title, 60)),
                'meta_description' => $this->cleanText($data['meta_description'] ?? Str::limit(strip_tags($content), 160)),
                'meta_keywords' => $metaKeywords,
                'focus_keyword' => $focusKeyword,
            ];
        } catch (\Exception $e) {
            Log::warning('Error generating SEO fields, using fallbacks: ' . $e->getMessage());
            // استخدام fallbacks عند فشل توليد SEO مع تنظيف
            $metaKeywords = $this->cleanKeywords($this->extractKeywords($content));
            $focusKeyword = $this->extractMainKeyword($topic, $content);
            $focusKeyword = trim(preg_replace('/[^\p{Arabic}\p{L}\p{N}\s-]/u', '', $focusKeyword));
            $focusKeyword = preg_replace('/\s+/u', ' ', $focusKeyword);
            
            return [
                'meta_title' => $this->cleanText(Str::limit($title, 60)),
                'meta_description' => $this->cleanText(Str::limit(strip_tags($content), 160)),
                'meta_keywords' => $metaKeywords,
                'focus_keyword' => $focusKeyword,
            ];
        }
    }

    /**
     * توليد Open Graph tags
     */
    private function generateOpenGraph(
        string $title,
        string $content,
        string $excerpt,
        AIModel $model,
        string $language
    ): array {
        return [
            'og_title' => Str::limit($title, 60),
            'og_description' => Str::limit($excerpt ?: strip_tags($content), 200),
            'og_type' => 'article',
            'og_locale' => $language === 'ar' ? 'ar_SA' : 'en_US',
        ];
    }

    /**
     * توليد Twitter Card tags
     */
    private function generateTwitterCard(
        string $title,
        string $content,
        string $excerpt,
        AIModel $model,
        string $language
    ): array {
        return [
            'twitter_card' => 'summary_large_image',
            'twitter_title' => Str::limit($title, 70),
            'twitter_description' => Str::limit($excerpt ?: strip_tags($content), 200),
        ];
    }

    /**
     * توليد Schema.org markup
     */
    private function generateSchema(
        string $title,
        string $content,
        string $excerpt,
        AIModel $model,
        string $language
    ): array {
        return [
            'schema_type' => 'Article',
            'schema_headline' => $title,
            'schema_description' => $excerpt ?: Str::limit(strip_tags($content), 200),
        ];
    }

    /**
     * توليد مرادفات الكلمة المفتاحية
     */
    private function generateKeywordSynonyms(
        string $keyword,
        AIModel $model,
        string $language
    ): string {
        $prompt = "أنت خبير في اللغة العربية. أعطني 8-12 مرادفاً أو كلمة مشابهة للكلمة المفتاحية التالية باللغة العربية:

الكلمة المفتاحية: {$keyword}

المتطلبات:
- استخدم فقط كلمات عربية صحيحة وكاملة
- لا تستخدم أي رموز غريبة مثل ? أو * أو [ أو ]
- لا تستخدم كلمات مكسورة أو غير مكتملة
- كل كلمة يجب أن تكون واضحة ومفهومة
- الكلمات يجب أن تكون ذات صلة قوية بالكلمة المفتاحية

يرجى إرجاع النتيجة كقائمة مفصولة بفواصل فقط، بدون أرقام أو نقاط أو رموز.";

        try {
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => 200,
                'temperature' => 0.6,
            ]);

            // تنظيف الاستجابة باستخدام cleanKeywords
            $synonyms = $this->cleanKeywords($response);
            
            return $synonyms;
        } catch (\Exception $e) {
            Log::warning('Error generating keyword synonyms: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * توليد مقتطف من المحتوى
     */
    private function generateExcerpt(string $content, string $language): string
    {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        return Str::limit($text, 150);
    }

    /**
     * توليد slug من العنوان
     */
    private function generateSlug(string $title): string
    {
        $slug = preg_replace('/\s+/', '-', trim($title));
        $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9-]/u', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * تنظيف النص من الرموز الغريبة
     */
    private function cleanText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // التحقق من ترميز UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        // إزالة BOM
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        
        // إزالة الأحرف غير الصالحة (الحفاظ على العربية والإنجليزية والأرقام والمسافات وعلامات الترقيم الأساسية)
        $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s.,!?;:()\-\'"]/u', '', $text);
        
        // تنظيف المسافات المتعددة
        $text = preg_replace('/\s+/u', ' ', $text);
        
        return trim($text);
    }

    /**
     * استخراج كلمات مفتاحية من المحتوى
     */
    private function extractKeywords(string $content, int $count = 10): string
    {
        $text = strip_tags($content);
        
        // تنظيف النص أولاً
        $text = $this->cleanText($text);
        
        // استخراج الكلمات (العربية والإنجليزية)
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Remove common Arabic stop words
        $stopWords = [
            'في', 'من', 'إلى', 'على', 'هذا', 'هذه', 'التي', 'الذي', 'كان', 'كانت', 
            'يكون', 'تكون', 'أن', 'إن', 'ما', 'لا', 'لم', 'لن', 'لكن', 'أو', 'و', 
            'مع', 'عن', 'عند', 'بين', 'خلال', 'حول', 'بعد', 'قبل', 'أثناء', 
            'لأن', 'لكي', 'حتى', 'إذا', 'إذ', 'إذن', 'إلا', 'إما', 'هو', 'هي', 
            'هم', 'هن', 'أنت', 'أنتم', 'أنتن', 'أنا', 'نحن', 'ذلك', 'تلك', 
            'هؤلاء', 'هناك', 'هنا', 'الآن', 'قد', 'قد', 'كل', 'بعض', 'أكثر', 'أقل'
        ];
        
        // تصفية الكلمات
        $filteredWords = [];
        foreach ($words as $word) {
            $word = trim($word);
            
            // تجاهل الكلمات القصيرة جداً (أقل من 3 أحرف)
            if (mb_strlen($word) < 3) {
                continue;
            }
            
            // تجاهل كلمات التوقف
            if (in_array($word, $stopWords, true)) {
                continue;
            }
            
            // تجاهل الكلمات التي تحتوي على أرقام فقط
            if (preg_match('/^\d+$/', $word)) {
                continue;
            }
            
            // تجاهل الكلمات التي تحتوي على رموز غريبة
            if (preg_match('/[?؟*+^$<>{}[\]()\\\]/u', $word)) {
                continue;
            }
            
            $filteredWords[] = $word;
        }
        
        // حساب تكرار الكلمات
        $wordFreq = array_count_values($filteredWords);
        arsort($wordFreq);
        
        // أخذ أكثر الكلمات تكراراً
        $keywords = array_slice(array_keys($wordFreq), 0, $count);
        
        // تنظيف الكلمات المفتاحية
        $cleanedKeywords = [];
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (mb_strlen($keyword) >= 2) {
                $cleanedKeywords[] = $keyword;
            }
        }
        
        return implode(', ', $cleanedKeywords);
    }

    /**
     * استخراج الكلمة المفتاحية الرئيسية
     */
    private function extractMainKeyword(string $topic, string $content): string
    {
        // Use topic as main keyword, or extract from content
        $topicWords = explode(' ', trim($topic));
        if (count($topicWords) <= 3) {
            return $topic;
        }
        
        // Extract first meaningful words from topic
        return implode(' ', array_slice($topicWords, 0, 3));
    }

    /**
     * Parse JSON response from AI
     */
    private function parseJSONResponse(string $response): array
    {
        // التحقق من ترميز UTF-8 وإصلاحه إذا لزم الأمر
        if (!mb_check_encoding($response, 'UTF-8')) {
            Log::warning('Invalid UTF-8 encoding detected in response, attempting to fix');
            $response = mb_convert_encoding($response, 'UTF-8', 'auto');
            // إذا فشل التحويل، جرب ترميزات أخرى
            if (!mb_check_encoding($response, 'UTF-8')) {
                $response = mb_convert_encoding($response, 'UTF-8', ['UTF-8', 'ISO-8859-1', 'Windows-1256']);
            }
        }
        
        // تنظيف النص من الأحرف غير الصالحة في UTF-8
        $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');
        
        // Try to extract JSON from response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            
            try {
                // استخدام JSON_INVALID_UTF8_IGNORE لتجاهل الأحرف غير الصالحة
                $decoded = json_decode($jsonString, true, 512, JSON_INVALID_UTF8_IGNORE);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                } else {
                    Log::warning('JSON decode error in parseJSONResponse', [
                        'error' => json_last_error_msg(),
                        'error_code' => json_last_error(),
                        'json_preview' => mb_substr($jsonString, 0, 200),
                    ]);
                }
            } catch (\JsonException $e) {
                Log::error('JSON exception in parseJSONResponse: ' . $e->getMessage(), [
                    'json_preview' => mb_substr($jsonString, 0, 200),
                ]);
            }
        }
        
        // If JSON parsing fails, try to extract data from text
        return [];
    }

    /**
     * تنظيف الكلمات المفتاحية من الرموز الغريبة والكلمات المكسورة
     */
    private function cleanKeywords(string $keywords): string
    {
        if (empty($keywords)) {
            return '';
        }

        // التحقق من ترميز UTF-8
        if (!mb_check_encoding($keywords, 'UTF-8')) {
            $keywords = mb_convert_encoding($keywords, 'UTF-8', 'auto');
        }

        // إزالة BOM إذا كان موجوداً
        $keywords = preg_replace('/^\xEF\xBB\xBF/', '', $keywords);

        // تقسيم الكلمات المفتاحية
        $keywordArray = preg_split('/[,،\n\r\t|]/u', $keywords);

        $cleanedKeywords = [];
        foreach ($keywordArray as $keyword) {
            // تنظيف كل كلمة
            $keyword = trim($keyword);
            
            // إزالة الأحرف غير الصالحة (الحفاظ على العربية والإنجليزية والأرقام والمسافات)
            $keyword = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s-]/u', '', $keyword);
            
            // إزالة المسافات المتعددة
            $keyword = preg_replace('/\s+/u', ' ', $keyword);
            $keyword = trim($keyword);

            // تجاهل الكلمات القصيرة جداً (أقل من 2 حرف) والكلمات الفارغة
            if (mb_strlen($keyword) < 2 || empty($keyword)) {
                continue;
            }

            // تجاهل الكلمات التي تحتوي على رموز مشبوهة
            if (preg_match('/[?؟*+^$<>{}[\]()\\\]/u', $keyword)) {
                continue;
            }

            // تجاهل الكلمات التي تبدأ أو تنتهي برموز غريبة
            if (preg_match('/^[^\p{Arabic}\p{L}\p{N}]|[^\p{Arabic}\p{L}\p{N}]$/u', $keyword)) {
                $keyword = preg_replace('/^[^\p{Arabic}\p{L}\p{N}]+|[^\p{Arabic}\p{L}\p{N}]+$/u', '', $keyword);
                $keyword = trim($keyword);
                if (mb_strlen($keyword) < 2) {
                    continue;
                }
            }

            // إضافة الكلمة المطهرة فقط إذا لم تكن موجودة بالفعل
            if (!empty($keyword) && !in_array($keyword, $cleanedKeywords)) {
                $cleanedKeywords[] = $keyword;
            }
        }

        // إرجاع الكلمات المفتاحية مفصولة بفواصل
        return implode(', ', $cleanedKeywords);
    }
}

