<?php

namespace App\Enums;

enum GameStatus: string
{
    case WantToPlay = 'want_to_play';
    case Installed = 'installed';
    case Playing = 'playing';
    case Completed = 'completed';
    case Dropped = 'dropped';

    public function label(): string
    {
        return __("app.statuses.{$this->value}");
    }

    public function icon(): string
    {
        return match ($this) {
            self::WantToPlay => 'bookmark_add',
            self::Installed => 'download_done',
            self::Playing => 'sports_esports',
            self::Completed => 'trophy',
            self::Dropped => 'block',
        };
    }
}
