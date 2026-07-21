<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameStatusEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['status', 'changed_at'];

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
