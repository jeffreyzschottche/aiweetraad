<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = Category::withCount('questions')
            ->orderBy('sort_order')
            ->get();

        $answeredQuestions = Question::where('status', 'published')
            ->where('answers_generated', true)
            ->whereHas('answers', fn ($query) => $query->where('status', '!=', 'failed'));

        $latest = (clone $answeredQuestions)
            ->with('category')
            ->withCount(['answers' => fn ($query) => $query->where('status', '!=', 'failed')])
            ->latest()
            ->limit(8)
            ->get();

        $popular = (clone $answeredQuestions)
            ->with('category')
            ->withCount(['answers' => fn ($query) => $query->where('status', '!=', 'failed')])
            ->orderByDesc('views')
            ->orderByDesc('answers_count')
            ->limit(6)
            ->get();

        return response()->json([
            'categories' => $categories,
            'latest' => $latest,
            'popular' => $popular,
            'stats' => [
                'questions' => (clone $answeredQuestions)->count(),
                'answers' => Answer::where('status', '!=', 'failed')->count(),
                'categories' => $categories->count(),
            ],
        ]);
    }
}
