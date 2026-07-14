<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GameList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'cover_path', 'default_platform', 'display_mode', 'is_public',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
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
}
