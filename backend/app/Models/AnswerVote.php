<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerVote extends Model
{
    protected $fillable = ['answer_id', 'voter_key', 'value'];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
