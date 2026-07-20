<?php

namespace App\Models;

use App\Enums\Achievement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $fillable = ['key', 'awarded_at'];

    protected function casts(): array
    {
        return [
            'key' => Achievement::class,
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
