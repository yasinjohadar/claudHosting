<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIModel;
use App\Services\Ai\AIContentSummaryService;
use App\Services\Ai\AIContentImprovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIContentController extends Controller
{
    public function __construct(
        private AIContentSummaryService $summaryService,
        private AIContentImprovementService $improvementService
    ) {}

    /**
     * تلخيص محتوى
     */
    public function summarize(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:short,long,bullet_points',
            'ai_model_id' => 'nullable|exists:ai_models,id',
        ]);

        try {
            $model = $validated['ai_model_id'] 
                ? AIModel::find($validated['ai_model_id'])
                : null;

            $summary = $this->summaryService->summarize(
                $validated['content'],
                $validated['type'],
                $model
            );

            return response()->json([
                'success' => true,
                'summary' => $summary->summary_text,
                'tokens_used' => $summary->tokens_used,
                'cost' => $summary->cost,
            ]);
        } catch (\Exception $e) {
            Log::error('Error summarizing content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحسين محتوى
     */
    public function improve(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'improvement_type' => 'required|in:general,grammar,clarity,all',
            'ai_model_id' => 'nullable|exists:ai_models,id',
        ]);

        try {
            $model = $validated['ai_model_id'] 
                ? AIModel::find($validated['ai_model_id'])
                : null;

            $result = $this->improvementService->improveContent(
                $validated['content'],
                [
                    'type' => $validated['improvement_type'],
                    'model' => $model,
                ]
            );

            return response()->json([
                'success' => true,
                'improved_content' => $result['content'] ?? $result,
                'suggestions' => $result['suggestions'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('Error improving content: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * فحص القواعد
     */
    public function grammarCheck(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'ai_model_id' => 'nullable|exists:ai_models,id',
        ]);

        try {
            $model = $validated['ai_model_id'] 
                ? AIModel::find($validated['ai_model_id'])
                : null;

            $result = $this->improvementService->checkGrammar($validated['text'], $model);

            return response()->json([
                'success' => true,
                'corrected_text' => $result['corrected'] ?? $result,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking grammar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
