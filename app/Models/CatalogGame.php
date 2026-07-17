<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CatalogGame extends Model
{
    protected $fillable = [
        'hltb_id', 'rawg_id', 'rawg_slug', 'title', 'normalized_title', 'cover_url',
        'screenshots', 'genres', 'genre_slugs', 'age_rating', 'platforms', 'platform_ids',
        'rawg_added', 'steam_id', 'rawg_synced_at',
        'main_story_minutes', 'completionist_minutes',
    ];

    protected function casts(): array
    {
        return [
            'screenshots' => 'array',
            'genres' => 'array',
            'genre_slugs' => 'array',
            'platforms' => 'array',
            'platform_ids' => 'array',
            'rawg_synced_at' => 'datetime',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(GameReview::class);
    }

    public function ageRatingLabel(): ?string
    {
        return match (Str::lower(trim((string) $this->age_rating))) {
            'everyone' => '6+',
            'everyone 10+' => '10+',
            'teen' => '13+',
            'mature' => '17+',
            'adults only' => '18+',
            default => null,
        };
    }
}
