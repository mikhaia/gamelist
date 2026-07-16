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
