<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Enums\Platform;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_list_id', 'catalog_game_id', 'title', 'normalized_title', 'status', 'platform', 'started_at', 'completed_at', 'hltb_id', 'cover_path',
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

    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            if (! $game->isDirty('status')) {
                return;
            }

            if ($game->status === GameStatus::Playing && $game->started_at === null) {
                $game->started_at = today();
            }

            if ($game->status === GameStatus::Completed && $game->completed_at === null) {
                $game->completed_at = today();
            }
        });

        static::deleting(function (Game $game): void {
            if ($game->cover_path) {
                Storage::disk('public')->delete($game->cover_path);
            }

            $game->screenshots()->pluck('path')->each(
                fn (string $path) => Storage::disk('public')->delete($path),
            );

            DB::table('notifications')->where('data->game_id', $game->id)->delete();
        });
    }

    public function gameList(): BelongsTo
    {
        return $this->belongsTo(GameList::class);
    }

    public function catalogGame(): BelongsTo
    {
        return $this->belongsTo(CatalogGame::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(GameScreenshot::class)->orderBy('sort_order')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GameComment::class)->orderBy('created_at');
    }

    public function scopeSortedForList(Builder $query, string $sort): Builder
    {
        if ($sort !== 'completed_at') {
            return $query;
        }

        return $query->reorder()
            ->orderByRaw('completed_at IS NULL')
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
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

    public function completionDuration(): ?string
    {
        if ($this->started_at === null || $this->completed_at === null) {
            return null;
        }

        $interval = $this->started_at->copy()->startOfDay()
            ->diffAsCarbonInterval($this->completed_at->copy()->startOfDay(), false);
        if ($interval->invert) {
            return null;
        }

        $months = ($interval->y * 12) + $interval->m;
        $days = $interval->d;
        $parts = [];

        if ($months > 0) {
            $parts[] = CarbonInterval::months($months)
                ->locale(app()->getLocale())
                ->forHumans(['parts' => 1]);
        }

        if ($days > 0 || $parts === []) {
            $parts[] = CarbonInterval::days(max(1, $days))
                ->locale(app()->getLocale())
                ->forHumans(['parts' => 1, 'skip' => ['week']]);
        }

        return implode(' ', $parts);
    }
}
