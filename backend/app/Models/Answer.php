<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id', 'ai_model_id', 'user_id', 'is_ai',
        'body', 'status', 'actual_provider', 'actual_model',
        'estimated_cost_usd', 'error_message', 'upvotes', 'downvotes',
    ];

    protected $casts = [
        'is_ai' => 'boolean',
        'estimated_cost_usd' => 'decimal:6',
    ];

    protected $hidden = [
        'actual_provider',
        'actual_model',
        'estimated_cost_usd',
        'error_message',
    ];

    protected $appends = ['score'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(AnswerVote::class);
    }

    public function getScoreAttribute(): int
    {
        return $this->upvotes - $this->downvotes;
    }
}
