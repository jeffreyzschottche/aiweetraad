<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnswerVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();
        $voterKey = 'u:' . $user->id;

        $questions = $user->questions()
            ->with('category')
            ->withCount(['answers' => fn ($query) => $query->where('status', '!=', 'failed')])
            ->latest()
            ->limit(12)
            ->get();

        $votes = AnswerVote::query()
            ->where('voter_key', $voterKey)
            ->where('value', 1)
            ->whereHas('answer', fn ($query) => $query->where('status', '!=', 'failed'))
            ->with(['answer.aiModel', 'answer.question.category'])
            ->latest()
            ->limit(12)
            ->get()
            ->filter(fn (AnswerVote $vote) => $vote->answer && $vote->answer->question)
            ->values();

        return response()->json([
            'data' => [
                'questions' => $questions,
                'worked' => $votes,
                'stats' => [
                    'questions' => $questions->count(),
                    'worked' => $votes->count(),
                ],
            ],
        ]);
    }
}
