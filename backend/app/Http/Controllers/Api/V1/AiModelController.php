<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AiModelController extends Controller
{
    public function index(): JsonResponse
    {
        $models = AiModel::enabled()->orderBy('sort_order')->get();

        return response()->json(['data' => $models]);
    }

    /**
     * Leaderboard: AI models ranked by the net score (likes - dislikes) of
     * all their answers. Mirrors the "top contributors" idea.
     */
    public function leaderboard(): JsonResponse
    {
        $scoreExpression = 'COALESCE(SUM(answers.upvotes), 0) - COALESCE(SUM(answers.downvotes), 0)';

        $models = AiModel::enabled()
            ->leftJoin('answers', function ($join) {
                $join->on('answers.ai_model_id', '=', 'ai_models.id')
                    ->where('answers.status', '!=', 'failed');
            })
            ->select(
                'ai_models.*',
                DB::raw('COALESCE(SUM(answers.upvotes), 0) as total_upvotes'),
                DB::raw('COALESCE(SUM(answers.downvotes), 0) as total_downvotes'),
                DB::raw('COUNT(answers.id) as answer_count'),
                DB::raw($scoreExpression . ' as score')
            )
            ->groupBy(
                'ai_models.id',
                'ai_models.name',
                'ai_models.slug',
                'ai_models.provider',
                'ai_models.model_identifier',
                'ai_models.tagline',
                'ai_models.accent_color',
                'ai_models.logo_url',
                'ai_models.system_prompt',
                'ai_models.enabled',
                'ai_models.sort_order',
                'ai_models.created_at',
                'ai_models.updated_at'
            )
            ->orderByDesc('score')
            ->orderByDesc('total_upvotes')
            ->orderBy('ai_models.sort_order')
            ->get();

        return response()->json(['data' => $models]);
    }
}
