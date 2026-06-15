<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Services\AI\AnswerGenerator;
use App\Services\QuestionModeration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Question::query()
            ->where('status', 'published')
            ->with('category')
            ->withCount(['answers' => fn ($query) => $query->where('status', '!=', 'failed')]);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $sort = $request->query('sort', 'recent');
        match ($sort) {
            'popular' => $query->orderByDesc('views'),
            default => $query->latest(),
        };

        return response()->json($query->paginate(15));
    }

    public function show(Request $request, Question $question): JsonResponse
    {
        $question->increment('views');
        $question->load([
            'category',
            'answers' => fn ($q) => $q->with('aiModel')
                ->orderByRaw("CASE WHEN status = 'failed' THEN 1 ELSE 0 END")
                ->orderByRaw('(CAST(upvotes AS SIGNED) - CAST(downvotes AS SIGNED)) desc')
                ->orderBy('id'),
        ]);

        $voterKey = $this->voterKey($request);
        $this->attachMyVotes($question->answers, $voterKey);

        $related = Question::where('category_id', $question->category_id)
            ->where('id', '!=', $question->id)
            ->where('status', 'published')
            ->latest()
            ->limit(6)
            ->get(['id', 'title', 'slug']);

        return response()->json([
            'data' => $question,
            'related' => $related,
        ]);
    }

    public function store(
        StoreQuestionRequest $request,
        AnswerGenerator $generator,
        QuestionModeration $moderation
    ): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        $questionsToday = Question::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($questionsToday >= 5) {
            return response()->json([
                'message' => 'Je daglimiet is bereikt.',
                'errors' => [
                    'title' => ['Je mag maximaal 5 vragen per dag stellen. Probeer het morgen opnieuw.'],
                ],
            ], 429);
        }

        $moderationResult = $moderation->inspect(
            (string) $request->title,
            (string) $request->input('body', '')
        );

        if (! $moderationResult['allowed']) {
            throw ValidationException::withMessages([
                'title' => [$moderationResult['message']],
            ]);
        }

        $question = Question::create([
            'title' => $request->title,
            'slug' => Question::makeUniqueSlug($request->title),
            'body' => $request->body,
            'category_id' => $request->category_id,
            'user_id' => $user->id,
            'status' => 'published',
        ]);

        // Generate (and cache) answers immediately so the user sees results.
        $generator->generateForQuestion($question);

        $question->load(['category', 'answers' => fn ($q) => $q->with('aiModel')]);

        return response()->json(['data' => $question], 201);
    }

    private function voterKey(Request $request): ?string
    {
        if ($user = $request->user()) {
            return 'u:' . $user->id;
        }
        if ($user = $request->user('sanctum')) {
            return 'u:' . $user->id;
        }
        return null;
    }

    private function attachMyVotes($answers, ?string $voterKey): void
    {
        if ($voterKey === null) {
            $answers->each(fn ($a) => $a->setAttribute('my_vote', 0));
            return;
        }

        $votes = \App\Models\AnswerVote::where('voter_key', $voterKey)
            ->whereIn('answer_id', $answers->pluck('id'))
            ->pluck('value', 'answer_id');

        $answers->each(fn ($a) => $a->setAttribute('my_vote', (int) ($votes[$a->id] ?? 0)));
    }
}
