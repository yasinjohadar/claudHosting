<?php

namespace App\Services\Ai;

use App\Models\AIConversation;
use App\Models\User;

class AIPromptService
{
    /**
     * الحصول على prompt للمساعد التعليمي
     */
    public function getChatbotPrompt(AIConversation $conversation): string
    {
        $context = $conversation->getContext();
        
        $prompt = "أنت مساعد تعليمي ذكي. مهمتك مساعدة الطلاب في فهم المواد الدراسية والإجابة على أسئلتهم.\n\n";
        
        if ($context) {
            $prompt .= "السياق:\n{$context}\n\n";
        }
        
        $prompt .= "تعليمات:\n";
        $prompt .= "- قدم إجابات واضحة ومفصلة\n";
        $prompt .= "- استخدم أمثلة لتوضيح المفاهيم\n";
        $prompt .= "- شجع الطالب على التفكير\n";
        $prompt .= "- إذا لم تكن متأكداً من الإجابة، اعترف بذلك\n";
        $prompt .= "- استخدم اللغة العربية بشكل صحيح\n";
        
        return $prompt;
    }

    /**
     * الحصول على prompt لتوليد الأسئلة
     */
    public function getQuestionGenerationPrompt(string $content, array $options): string
    {
        $questionType = $options['question_type'] ?? 'mixed';
        $numberOfQuestions = $options['number_of_questions'] ?? 5;
        $difficulty = $options['difficulty_level'] ?? 'mixed';
        
        $questionTypeText = $this->getQuestionTypeText($questionType);
        $difficultyText = $this->getDifficultyText($difficulty);
        
        // تحديد أنواع الأسئلة المطلوبة
        $typeInstructions = $this->getTypeInstructions($questionType, $numberOfQuestions);
        
        $prompt = "أنت خبير تعليمي متخصص في إنشاء الأسئلة والاختبارات.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "أنشئ بالضبط **{$numberOfQuestions} أسئلة** تعليمية متنوعة من المحتوى التالي.\n";
        $prompt .= "⚠️ **مهم جداً**: يجب أن يكون العدد بالضبط {$numberOfQuestions} أسئلة، لا أكثر ولا أقل.\n\n";
        $prompt .= "## المحتوى:\n{$content}\n\n";
        $prompt .= "## المتطلبات:\n";
        $prompt .= "1. **العدد المطلوب**: {$numberOfQuestions} أسئلة بالضبط (إلزامي - لا تقبل أي عدد آخر)\n";
        $prompt .= "2. **نوع الأسئلة**: {$questionTypeText}\n";
        $prompt .= "3. **مستوى الصعوبة**: {$difficultyText}\n";
        $prompt .= "{$typeInstructions}\n\n";
        $prompt .= "## تنسيق الإخراج:\n";
        $prompt .= "أرجع JSON array يحتوي على **بالضبط {$numberOfQuestions} كائنات** (لا أكثر ولا أقل):\n\n";
        $prompt .= "```json\n";
        $prompt .= "[\n";
        for ($i = 1; $i <= min(3, $numberOfQuestions); $i++) {
            $prompt .= "  {\n";
            $prompt .= "    \"type\": \"single_choice|multiple_choice|true_false|short_answer\",\n";
            $prompt .= "    \"question\": \"نص السؤال {$i}\",\n";
            $prompt .= "    \"options\": [\"خيار أ\", \"خيار ب\", \"خيار ج\", \"خيار د\"],\n";
            $prompt .= "    \"correct_answer\": \"الإجابة الصحيحة\",\n";
            $prompt .= "    \"explanation\": \"شرح مختصر للإجابة\",\n";
            $prompt .= "    \"difficulty\": \"easy|medium|hard\"\n";
            $prompt .= "  }" . ($i < min(3, $numberOfQuestions) ? "," : "") . "\n";
        }
        if ($numberOfQuestions > 3) {
            $prompt .= "  ... (كرر نفس البنية للأسئلة من 4 إلى {$numberOfQuestions}) ...\n";
        }
        $prompt .= "]\n";
        $prompt .= "```\n\n";
        $prompt .= "## ⚠️ تحذير مهم:\n";
        $prompt .= "- يجب أن يحتوي الرد على **بالضبط {$numberOfQuestions} أسئلة**\n";
        $lessOne = $numberOfQuestions - 1;
        $plusOne = $numberOfQuestions + 1;
        $prompt .= "- لا تقبل أي عدد آخر (لا {$lessOne} ولا {$plusOne})\n";
        $prompt .= "- تأكد من أن JSON array يحتوي على {$numberOfQuestions} عنصر بالضبط\n";
        $prompt .= "- إذا لم تستطع إنشاء {$numberOfQuestions} أسئلة، أبلغ بذلك بوضوح\n";
        
        return $prompt;
    }
    
    /**
     * الحصول على تعليمات نوع السؤال
     */
    private function getTypeInstructions(string $type, int $count): string
    {
        if ($type === 'mixed') {
            return "4. **توزيع الأنواع**: وزّع الأسئلة بين اختيار من متعدد، صح/خطأ، وإجابة قصيرة";
        }
        
        $typeNames = [
            'single_choice' => 'اختيار واحد (4 خيارات لكل سؤال)',
            'multiple_choice' => 'اختيار متعدد (4 خيارات، يمكن أن تكون أكثر من إجابة صحيحة)',
            'true_false' => 'صح/خطأ',
            'short_answer' => 'إجابة قصيرة',
            'essay' => 'مقالي',
        ];
        
        $typeName = $typeNames[$type] ?? $type;
        return "4. **نوع كل الأسئلة**: {$typeName}";
    }

    /**
     * الحصول على prompt لحل السؤال
     */
    public function getQuestionSolvingPrompt(Question $question): string
    {
        $prompt = "أنت معلم محترف. مهمتك حل السؤال التالي:\n\n";
        $prompt .= "نوع السؤال: {$question->type}\n";
        $prompt .= "السؤال: " . ($question->content ?? $question->title ?? '') . "\n\n";
        
        if ($question->options && $question->options->count() > 0) {
            $prompt .= "الخيارات:\n";
            foreach ($question->options as $index => $option) {
                $prompt .= ($index + 1) . ". {$option->content}\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "المطلوب:\n";
        $prompt .= "1. حل السؤال بشكل صحيح\n";
        $prompt .= "2. شرح طريقة الحل\n";
        $prompt .= "3. تقدير درجة الثقة في الحل (0-1)\n\n";
        $prompt .= "قم بالإجابة بصيغة JSON:\n";
        $prompt .= "{\n";
        $prompt .= "  \"solution\": \"الحل\",\n";
        $prompt .= "  \"explanation\": \"الشرح\",\n";
        $prompt .= "  \"confidence_score\": 0.95\n";
        $prompt .= "}\n";
        
        return $prompt;
    }

    /**
     * بناء سياق من بيانات
     */
    public function buildContext(array $data): string
    {
        $context = [];
        
        if (isset($data['subject'])) {
            $context[] = "المادة: {$data['subject']}";
        }
        
        if (isset($data['lesson'])) {
            $context[] = "الدرس: {$data['lesson']}";
        }
        
        if (isset($data['topic'])) {
            $context[] = "الموضوع: {$data['topic']}";
        }
        
        return implode("\n", $context);
    }

    /**
     * الحصول على نص نوع السؤال
     */
    private function getQuestionTypeText(string $type): string
    {
        $types = [
            'single_choice' => 'اختيار واحد',
            'multiple_choice' => 'اختيار متعدد',
            'true_false' => 'صح/خطأ',
            'short_answer' => 'إجابة قصيرة',
            'essay' => 'مقالي',
            'matching' => 'مطابقة',
            'ordering' => 'ترتيب',
            'fill_blanks' => 'ملء الفراغات',
            'numerical' => 'رقمي',
            'drag_drop' => 'سحب وإفلات',
            'mixed' => 'مختلط (جميع الأنواع)',
        ];
        
        return $types[$type] ?? $type;
    }

    /**
     * الحصول على نص مستوى الصعوبة
     */
    private function getDifficultyText(string $difficulty): string
    {
        $difficulties = [
            'easy' => 'سهل',
            'medium' => 'متوسط',
            'hard' => 'صعب',
            'mixed' => 'مختلط',
        ];
        
        return $difficulties[$difficulty] ?? $difficulty;
    }

    /**
     * الحصول على prompt لتصحيح الإجابات المقالية
     */
    public function getEssayGradingPrompt(Question $question, string $studentAnswer, array $criteria = []): string
    {
        $maxPoints = $question->default_points ?? 10;
        $questionText = $question->content ?? $question->title ?? '';
        $explanation = $question->explanation ?? '';
        
        // بناء معايير التصحيح
        $criteriaText = $this->buildCriteriaText($criteria);
        
        $prompt = "أنت مصحح محترف ومتخصص في تقييم الإجابات المقالية.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "قم بتصحيح وتقييم الإجابة المقالية التالية بناءً على المعايير المحددة.\n\n";
        
        $prompt .= "## السؤال:\n";
        $prompt .= "{$questionText}\n\n";
        
        if (!empty($explanation)) {
            $prompt .= "## ملاحظات أو إرشادات:\n";
            $prompt .= "{$explanation}\n\n";
        }
        
        $prompt .= "## إجابة الطالب:\n";
        $prompt .= "{$studentAnswer}\n\n";
        
        $prompt .= "## معايير التصحيح:\n";
        $prompt .= $criteriaText;
        $prompt .= "\n\n";
        
        $prompt .= "## المطلوب:\n";
        $prompt .= "قم بتصحيح الإجابة وتقييمها بناءً على المعايير أعلاه.\n\n";
        $prompt .= "## تنسيق الإخراج:\n";
        $prompt .= "أرجع JSON object بالبنية التالية:\n\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"points\": " . number_format($maxPoints, 2) . ",\n";
        $prompt .= "  \"max_points\": " . number_format($maxPoints, 2) . ",\n";
        $prompt .= "  \"criteria_scores\": {\n";
        foreach ($criteria as $key => $criterion) {
            $weight = $criterion['weight'] ?? 0.2;
            $maxForCriteria = $maxPoints * $weight;
            $prompt .= "    \"{$key}\": {\n";
            $prompt .= "      \"score\": " . number_format($maxForCriteria, 2) . ",\n";
            $prompt .= "      \"max_score\": " . number_format($maxForCriteria, 2) . ",\n";
            $prompt .= "      \"feedback\": \"ملاحظات حول هذا المعيار\"\n";
            $prompt .= "    }" . (array_key_last($criteria) !== $key ? ',' : '') . "\n";
        }
        $prompt .= "  },\n";
        $prompt .= "  \"feedback\": \"ملاحظات عامة عن الإجابة\",\n";
        $prompt .= "  \"strengths\": [\"نقطة قوة 1\", \"نقطة قوة 2\"],\n";
        $prompt .= "  \"weaknesses\": [\"نقطة ضعف 1\", \"نقطة ضعف 2\"],\n";
        $prompt .= "  \"suggestions\": [\"اقتراح تحسين 1\", \"اقتراح تحسين 2\"]\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        
        $prompt .= "## تعليمات مهمة:\n";
        $prompt .= "- يجب أن تكون الدرجة الإجمالية (points) بين 0 و {$maxPoints}\n";
        $prompt .= "- يجب أن يكون مجموع criteria_scores قريباً من points\n";
        $prompt .= "- كن عادلاً ودقيقاً في التقييم\n";
        $prompt .= "- قدم ملاحظات بناءة وواضحة\n";
        $prompt .= "- ركز على المحتوى والفهم أكثر من الشكل\n";
        
        return $prompt;
    }

    /**
     * بناء نص معايير التصحيح
     */
    private function buildCriteriaText(array $criteria): string
    {
        if (empty($criteria)) {
            return "- المحتوى والشمولية\n- التنظيم والترابط\n- اللغة والقواعد\n- الإبداع والتفكير النقدي\n- الطول والتفصيل";
        }

        $text = '';
        foreach ($criteria as $key => $criterion) {
            $weight = ($criterion['weight'] ?? 0.2) * 100;
            $description = $criterion['description'] ?? $criterion['name'] ?? $key;
            $text .= "- **{$description}** (الوزن: {$weight}%)\n";
        }

        return trim($text);
    }

    /**
     * الحصول على prompt لإنشاء معايير تصحيح (Rubric)
     */
    public function getRubricPrompt(Question $question): string
    {
        $questionText = $question->content ?? $question->title ?? '';
        $maxPoints = $question->default_points ?? 10;
        
        $prompt = "أنت خبير تعليمي متخصص في وضع معايير التصحيح (Rubrics).\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "أنشئ معايير تصحيح واضحة ومناسبة للسؤال المقالي التالي.\n\n";
        
        $prompt .= "## السؤال:\n";
        $prompt .= "{$questionText}\n\n";
        
        $prompt .= "## المطلوب:\n";
        $prompt .= "أنشئ معايير تصحيح تتضمن:\n";
        $prompt .= "1. المحتوى والشمولية (30%)\n";
        $prompt .= "2. التنظيم والترابط (25%)\n";
        $prompt .= "3. اللغة والقواعد (20%)\n";
        $prompt .= "4. الإبداع والتفكير النقدي (15%)\n";
        $prompt .= "5. الطول والتفصيل (10%)\n\n";
        
        $prompt .= "## تنسيق الإخراج:\n";
        $prompt .= "أرجع JSON object يحتوي على معايير التصحيح:\n\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"criteria\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"name\": \"المحتوى والشمولية\",\n";
        $prompt .= "      \"weight\": 0.3,\n";
        $prompt .= "      \"description\": \"وصف المعيار\",\n";
        $prompt .= "      \"levels\": [\n";
        $prompt .= "        {\"level\": \"ممتاز\", \"score\": 100, \"description\": \"...\"},\n";
        $prompt .= "        {\"level\": \"جيد\", \"score\": 75, \"description\": \"...\"},\n";
        $prompt .= "        {\"level\": \"مقبول\", \"score\": 50, \"description\": \"...\"},\n";
        $prompt .= "        {\"level\": \"ضعيف\", \"score\": 25, \"description\": \"...\"}\n";
        $prompt .= "      ]\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";
        $prompt .= "```\n";
        
        return $prompt;
    }

    /**
     * الحصول على prompt لتلخيص المحتوى
     */
    public function getContentSummaryPrompt(string $content, string $type = 'short'): string
    {
        $typeText = match($type) {
            'short' => 'تلخيص قصير ومختصر (3-5 جمل)',
            'long' => 'تلخيص مفصل (10-15 جمل)',
            'bullet_points' => 'نقاط رئيسية في شكل قائمة',
            default => 'تلخيص',
        };

        $prompt = "أنت خبير في التلخيص والتحليل التعليمي.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "قم بتلخيص المحتوى التالي بشكل {$typeText}.\n\n";
        $prompt .= "## المحتوى:\n";
        $prompt .= "{$content}\n\n";
        
        if ($type === 'bullet_points') {
            $prompt .= "## المطلوب:\n";
            $prompt .= "- قدم النقاط الرئيسية في شكل قائمة\n";
            $prompt .= "- كل نقطة في سطر منفصل\n";
            $prompt .= "- ركز على الأفكار الأساسية والمفاهيم المهمة\n";
            $prompt .= "- استخدم اللغة العربية الفصحى\n";
        } else {
            $prompt .= "## المطلوب:\n";
            $prompt .= "- اكتب تلخيصاً واضحاً ومترابطاً\n";
            $prompt .= "- احتفظ بالأفكار الأساسية\n";
            $prompt .= "- استخدم اللغة العربية الفصحى\n";
            $prompt .= "- تجنب الإطالة غير الضرورية\n";
        }

        return $prompt;
    }

    /**
     * الحصول على prompt لتحسين المحتوى
     */
    public function getContentImprovementPrompt(string $content, string $type = 'general'): string
    {
        $typeInstructions = match($type) {
            'grammar' => 'ركز على تصحيح الأخطاء النحوية والإملائية فقط.',
            'clarity' => 'ركز على تحسين الوضوح وسهولة الفهم.',
            'all' => 'قم بتحسين المحتوى بشكل شامل (القواعد، الوضوح، التنظيم).',
            default => 'قم بتحسين المحتوى بشكل عام.',
        };

        $prompt = "أنت محرر محترف متخصص في تحسين المحتوى التعليمي.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "{$typeInstructions}\n\n";
        $prompt .= "## المحتوى الأصلي:\n";
        $prompt .= "{$content}\n\n";
        $prompt .= "## المطلوب:\n";
        $prompt .= "- احتفظ بالمعنى والأفكار الأساسية\n";
        $prompt .= "- حسّن الوضوح والدقة\n";
        $prompt .= "- استخدم اللغة العربية الفصحى الصحيحة\n";
        $prompt .= "- أعد كتابة المحتوى المحسّن\n\n";
        $prompt .= "أرجع المحتوى المحسّن فقط بدون شرح أو تعليقات.";

        return $prompt;
    }

    /**
     * الحصول على prompt لفحص القواعد
     */
    public function getGrammarCheckPrompt(string $text): string
    {
        $prompt = "أنت خبير في القواعد النحوية والإملائية للغة العربية.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "قم بفحص النص التالي وتصحيح الأخطاء النحوية والإملائية.\n\n";
        $prompt .= "## النص:\n";
        $prompt .= "{$text}\n\n";
        $prompt .= "## المطلوب:\n";
        $prompt .= "أرجع JSON object يحتوي على:\n";
        $prompt .= "{\n";
        $prompt .= "  \"corrected\": \"النص المصحح\",\n";
        $prompt .= "  \"errors\": [\n";
        $prompt .= "    {\"original\": \"النص الخاطئ\", \"corrected\": \"التصحيح\", \"explanation\": \"الشرح\"}\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * الحصول على prompt لتحسين الوضوح
     */
    public function getClarityEnhancementPrompt(string $text): string
    {
        $prompt = "أنت خبير في كتابة المحتوى التعليمي الواضح.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "حسّن وضوح وسهولة فهم النص التالي.\n\n";
        $prompt .= "## النص:\n";
        $prompt .= "{$text}\n\n";
        $prompt .= "## المطلوب:\n";
        $prompt .= "- أعد كتابة النص بشكل أوضح وأسهل للفهم\n";
        $prompt .= "- استخدم جمل قصيرة وواضحة\n";
        $prompt .= "- احتفظ بالمعنى والأفكار\n";
        $prompt .= "- استخدم أمثلة إن أمكن\n\n";
        $prompt .= "أرجع النص المحسّن فقط.";

        return $prompt;
    }

    /**
     * الحصول على prompt لاقتراح تحسينات
     */
    public function getImprovementSuggestionsPrompt(string $content): string
    {
        $prompt = "أنت خبير في تحليل وتقييم المحتوى التعليمي.\n\n";
        $prompt .= "## المهمة:\n";
        $prompt .= "قم بتحليل المحتوى التالي واقترح تحسينات واضحة ومحددة.\n\n";
        $prompt .= "## المحتوى:\n";
        $prompt .= "{$content}\n\n";
        $prompt .= "## المطلوب:\n";
        $prompt .= "قدم قائمة بتحسينات مقترحة في شكل نقاط:\n";
        $prompt .= "- كل تحسين في نقطة منفصلة\n";
        $prompt .= "- كن محدداً وواضحاً\n";
        $prompt .= "- ركز على القواعد، الوضوح، التنظيم\n";
        $prompt .= "- قدم اقتراحات عملية قابلة للتطبيق\n";

        return $prompt;
    }

    /**
     * الحصول على prompt لتوليد ملاحظات للطلاب
     */
    public function getStudentFeedbackPrompt(User $student, array $data): string
    {
        $type = $data['type'] ?? 'general';
        
        $prompt = "أنت مستشار تعليمي محترف. مهمتك تقديم ملاحظات بناءة ومحفزة للطلاب.\n\n";
        $prompt .= "## معلومات الطالب:\n";
        $prompt .= "الاسم: {$student->name}\n\n";

        if ($type === 'performance' && isset($data['quiz_results'])) {
            $results = $data['quiz_results'];
            $prompt .= "## نتائج الاختبار:\n";
            if (isset($results['quiz_title'])) {
                $prompt .= "الاختبار: {$results['quiz_title']}\n";
            }
            if (isset($results['score']) && isset($results['max_score'])) {
                $prompt .= "الدرجة: {$results['score']} / {$results['max_score']}\n";
            }
            if (isset($results['percentage'])) {
                $prompt .= "النسبة المئوية: {$results['percentage']}%\n";
            }
            if (isset($results['correct_answers']) && isset($results['total_questions'])) {
                $prompt .= "الإجابات الصحيحة: {$results['correct_answers']} / {$results['total_questions']}\n";
            }
            $prompt .= "\n";
        } elseif ($type === 'improvement' && isset($data['weak_areas'])) {
            $prompt .= "## المجالات التي تحتاج تحسين:\n";
            foreach ($data['weak_areas'] as $area) {
                $prompt .= "- {$area}\n";
            }
            $prompt .= "\n";
        } else {
            // بيانات عامة شاملة
            $prompt .= "## الإحصائيات العامة:\n";
            
            if (isset($data['total_quizzes'])) {
                $prompt .= "عدد الاختبارات المكتملة: {$data['completed_quizzes']}\n";
            }
            if (isset($data['average_score'])) {
                $prompt .= "متوسط الدرجات: {$data['average_score']}%\n";
            }
            if (isset($data['best_score']) && isset($data['best_quiz'])) {
                $prompt .= "أفضل أداء: {$data['best_score']}% في اختبار \"{$data['best_quiz']}\"\n";
            }
            if (isset($data['worst_score']) && isset($data['worst_quiz'])) {
                $prompt .= "أدنى أداء: {$data['worst_score']}% في اختبار \"{$data['worst_quiz']}\"\n";
            }
            if (isset($data['total_courses'])) {
                $prompt .= "عدد الكورسات المسجل فيها: {$data['total_courses']}\n";
            }
            if (isset($data['last_activity'])) {
                $prompt .= "آخر نشاط: {$data['last_activity']}\n";
            }
            $prompt .= "\n";
            
            // إضافة التعليمات المخصصة إن وجدت
            if (isset($data['custom_instructions']) && !empty($data['custom_instructions'])) {
                $prompt .= "## تعليمات خاصة:\n";
                $prompt .= "{$data['custom_instructions']}\n\n";
            }
        }

        $prompt .= "## المطلوب:\n";
        $prompt .= "قدم ملاحظات بناءة ومحفزة تتضمن:\n";
        $prompt .= "1. تقييم عام للأداء\n";
        $prompt .= "2. نقاط القوة\n";
        $prompt .= "3. نقاط التحسين\n";
        $prompt .= "4. اقتراحات عملية للتحسين\n\n";
        
        $prompt .= "## تنسيق الإخراج (اختياري):\n";
        $prompt .= "يمكنك إرجاع JSON:\n";
        $prompt .= "{\n";
        $prompt .= "  \"feedback\": \"الملاحظات العامة\",\n";
        $prompt .= "  \"suggestions\": [\"اقتراح 1\", \"اقتراح 2\"]\n";
        $prompt .= "}\n\n";
        $prompt .= "أو يمكنك إرجاع نص عادي مع نقاط واضحة.\n";
        $prompt .= "كن محفزاً وبناءً في نبرتك.";

        return $prompt;
    }
}

