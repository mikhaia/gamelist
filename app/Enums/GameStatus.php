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

    public function historyLabel(bool $repeated = false): string
    {
        $label = match ($this) {
            self::WantToPlay => 'Хочет сыграть',
            self::Installed => 'Установлена',
            self::Playing => 'Начал играть',
            self::Completed => 'Пройдена',
            self::Dropped => 'Брошена',
        };

        return $repeated ? 'Снова '.mb_strtolower($label) : $label;
    }
}
