<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'normalized_title', 'status', 'platform', 'started_at', 'completed_at', 'hltb_id', 'cover_path',
        'source_cover_url', 'main_story_minutes', 'main_extra_minutes',
        'completionist_minutes', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'platform' => Platform::class,
            'started_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function gameList(): BelongsTo
    {
        return $this->belongsTo(GameList::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function formattedTime(?int $minutes): ?string
    {
        if (! $minutes) {
            return null;
        }

        $hours = round($minutes / 60, 1);

        return trans_choice('app.hours', (int) ceil($hours), ['count' => $hours]);
    }
}
