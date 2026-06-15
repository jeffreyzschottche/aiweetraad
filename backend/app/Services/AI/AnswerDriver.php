<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Question;

interface AnswerDriver
{
    /**
     * Generate a single answer body (plain text / light markdown) for the
     * given question, in the voice of the given AI model.
     */
    public function generate(Question $question, AiModel $model): string;
}
