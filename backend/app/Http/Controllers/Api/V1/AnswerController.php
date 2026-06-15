<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoteRequest;
use App\Models\Answer;
use App\Models\AnswerVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    public function vote(VoteRequest $request, Answer $answer): JsonResponse
    {
        if ($answer->status === 'failed') {
            return response()->json([
                'message' => 'Op een niet-beschikbaar antwoord kan niet worden gestemd.',
            ], 422);
        }

        $voterKey = $this->voterKey($request);

        if ($voterKey === null) {
            return response()->json(['message' => 'Stem kon niet worden geregistreerd.'], 422);
        }

        $value = (int) $request->value;

        DB::transaction(function () use ($answer, $voterKey, $value) {
            $existing = AnswerVote::where('answer_id', $answer->id)
                ->where('voter_key', $voterKey)
                ->lockForUpdate()
                ->first();

            $old = $existing?->value ?? 0;

            if ($existing && $existing->value === $value) {
                // Same button pressed again -> remove the vote (toggle off).
                $existing->delete();
                $new = 0;
            } elseif ($existing) {
                $existing->update(['value' => $value]);
                $new = $value;
            } else {
                AnswerVote::create([
                    'answer_id' => $answer->id,
                    'voter_key' => $voterKey,
                    'value' => $value,
                ]);
                $new = $value;
            }

            // Apply the change as a delta so any seeded baseline counts are
            // preserved (we don't recount from the votes table).
            $upDelta = ($new === 1 ? 1 : 0) - ($old === 1 ? 1 : 0);
            $downDelta = ($new === -1 ? 1 : 0) - ($old === -1 ? 1 : 0);

            $answer->upvotes = max(0, $answer->upvotes + $upDelta);
            $answer->downvotes = max(0, $answer->downvotes + $downDelta);
            $answer->save();
        });

        $answer->refresh();
        $myVote = (int) (AnswerVote::where('answer_id', $answer->id)
            ->where('voter_key', $voterKey)
            ->value('value') ?? 0);

        return response()->json([
            'upvotes' => $answer->upvotes,
            'downvotes' => $answer->downvotes,
            'score' => $answer->score,
            'my_vote' => $myVote,
        ]);
    }

    private function voterKey(VoteRequest $request): ?string
    {
        if ($user = $request->user()) {
            return 'u:' . $user->id;
        }
        if ($user = $request->user('sanctum')) {
            return 'u:' . $user->id;
        }
        return null;
    }
}
