<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogGame extends Model
{
    protected $fillable = [
        'hltb_id', 'title', 'normalized_title', 'cover_url',
        'main_story_minutes', 'completionist_minutes',
    ];

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(GameReview::class);
    }
}
