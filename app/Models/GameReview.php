<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameReview extends Model
{
    protected $fillable = ['user_id', 'catalog_game_id', 'rating', 'body'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catalogGame(): BelongsTo
    {
        return $this->belongsTo(CatalogGame::class);
    }
}
