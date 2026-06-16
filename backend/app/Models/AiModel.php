<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'name', 'slug', 'provider', 'model_identifier', 'tagline',
        'accent_color', 'logo_url', 'system_prompt', 'enabled', 'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $hidden = ['provider', 'model_identifier', 'system_prompt'];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
