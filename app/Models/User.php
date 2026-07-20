<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'login', 'email', 'password', 'avatar_path', 'profile_cover_path', 'last_seen_at', 'inactive_reminder_sent_at',
    ];

    public function gameLists(): HasMany
    {
        return $this->hasMany(GameList::class);
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')->withTimestamps();
    }

    public function favoriteGames(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'favorite_games')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function gameReviews(): HasMany
    {
        return $this->hasMany(GameReview::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function getProfileCoverUrlAttribute(): ?string
    {
        return $this->profile_cover_path ? Storage::disk('public')->url($this->profile_cover_path) : null;
    }

    public function isFriendsWith(User $user): bool
    {
        return $this->friends()->whereKey($user->getKey())->exists();
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThan(now()->subHour());
    }

    public function activityLabel(): string
    {
        if ($this->last_seen_at === null) {
            return __('app.activity.unknown');
        }

        $elapsedSeconds = max(0, now()->getTimestamp() - $this->last_seen_at->getTimestamp());
        if ($elapsedSeconds < 3600) {
            return __('app.activity.online');
        }

        [$unit, $count] = match (true) {
            $elapsedSeconds < 86400 => ['hours', intdiv($elapsedSeconds, 3600)],
            $elapsedSeconds < 2592000 => ['days', intdiv($elapsedSeconds, 86400)],
            default => ['months', intdiv($elapsedSeconds, 2592000)],
        };

        return __('app.activity.last_seen', [
            'time' => trans_choice("app.activity.{$unit}", max(1, $count), ['count' => max(1, $count)]),
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'inactive_reminder_sent_at' => 'datetime',
        ];
    }
}
