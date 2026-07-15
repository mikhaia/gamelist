<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GameList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'cover_path', 'default_platform', 'available_statuses', 'display_mode', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'available_statuses' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class)->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function getPublicPathAttribute(): string
    {
        return route('public.lists.show', [$this->user->login, $this->slug]);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /** @return array<int, GameStatus> */
    public function availableStatuses(): array
    {
        $configured = $this->available_statuses;

        if (! is_array($configured) || $configured === []) {
            return GameStatus::cases();
        }

        $statuses = array_values(array_filter(
            GameStatus::cases(),
            fn (GameStatus $status): bool => in_array($status->value, $configured, true),
        ));

        return $statuses === [] ? GameStatus::cases() : $statuses;
    }

    /** @return array<int, string> */
    public function availableStatusValues(): array
    {
        return array_map(fn (GameStatus $status): string => $status->value, $this->availableStatuses());
    }

    public function defaultStatus(): GameStatus
    {
        return $this->availableStatuses()[0];
    }
}
