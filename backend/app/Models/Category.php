<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    private const LEGACY_AI_TRICKS_SLUG = 'omas-oudste-trucjes';
    private const AI_TRICKS_SLUG = 'ai-trucjes';

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color', 'sort_order',
    ];

    protected $appends = [
        'canonical_slug',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function getNameAttribute(?string $value): ?string
    {
        if ($this->isAiTricksCategory()) {
            return 'AI-trucjes';
        }

        return $value;
    }

    public function getSlugAttribute(?string $value): ?string
    {
        if ($this->isAiTricksCategory($value)) {
            return self::AI_TRICKS_SLUG;
        }

        return $value;
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        if ($this->isAiTricksCategory()) {
            return 'Slimme huis-, tuin- en keukenoplossingen in een AI-jasje.';
        }

        return $value;
    }

    public function getIconAttribute(?string $value): ?string
    {
        if ($this->isAiTricksCategory()) {
            return '✨';
        }

        return $value;
    }

    public function getCanonicalSlugAttribute(): ?string
    {
        return $this->slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field === null || $field === 'slug') {
            if (in_array($value, [self::AI_TRICKS_SLUG, self::LEGACY_AI_TRICKS_SLUG], true)) {
                return static::query()
                    ->where('slug', self::AI_TRICKS_SLUG)
                    ->first()
                    ?: static::query()->where('slug', self::LEGACY_AI_TRICKS_SLUG)->first();
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }

    private function isAiTricksCategory(?string $slug = null): bool
    {
        $slug ??= $this->attributes['slug'] ?? null;
        $name = $this->attributes['name'] ?? null;

        return in_array($slug, [self::AI_TRICKS_SLUG, self::LEGACY_AI_TRICKS_SLUG], true)
            || $name === "Oma's Oudste Trucjes";
    }
}
