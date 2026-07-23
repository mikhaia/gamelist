<?php

namespace App\Enums;

enum GameStatus: string
{
    case WantToPlay = 'want_to_play';
    case Installed = 'installed';
    case Playing = 'playing';
    case Completed = 'completed';
    case WantToReplay = 'want_to_replay';
    case Replaying = 'replaying';
    case Completed100 = 'completed_100';
    case Dropped = 'dropped';

    /** @return array<int, self> */
    public static function defaultCases(): array
    {
        return [self::WantToPlay, self::Playing, self::Completed, self::Dropped];
    }

    /** @return array<int, self> */
    public static function legacyCases(): array
    {
        return [self::WantToPlay, self::Installed, self::Playing, self::Completed, self::Dropped];
    }

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
            self::WantToReplay => 'restart_alt',
            self::Replaying => 'progress_activity',
            self::Completed100 => 'workspace_premium',
            self::Dropped => 'block',
        };
    }

    public function isInProgress(): bool
    {
        return in_array($this, [self::Playing, self::Replaying], true);
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::Completed, self::Completed100], true);
    }

    public function historyLabel(bool $repeated = false): string
    {
        $label = match ($this) {
            self::WantToPlay => 'Хочет сыграть',
            self::Installed => 'Установлена',
            self::Playing => 'Начал играть',
            self::Completed => 'Пройдена',
            self::WantToReplay => 'Хочет перепройти',
            self::Replaying => 'Начал перепрохождение',
            self::Completed100 => 'Пройдена на 100%',
            self::Dropped => 'Брошена',
        };

        return $repeated ? 'Снова '.mb_strtolower($label) : $label;
    }
}
