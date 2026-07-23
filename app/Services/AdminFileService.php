<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameList;
use App\Models\GameScreenshot;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminFileService
{
    private const TYPES = [
        'screenshots' => ['label' => 'Скриншоты', 'directory' => 'game-screenshots', 'icon' => 'photo_library'],
        'avatars' => ['label' => 'Аватары', 'directory' => 'avatars', 'icon' => 'person'],
        'list-covers' => ['label' => 'Обложки списков', 'directory' => 'list-covers', 'icon' => 'view_list'],
        'game-covers' => ['label' => 'Обложки игр', 'directory' => 'game-covers', 'icon' => 'sports_esports'],
    ];

    /** @return array<string, array{label: string, directory: string, icon: string}> */
    public function types(): array
    {
        return self::TYPES;
    }

    /** @return LengthAwarePaginator<array<string, mixed>> */
    public function paginate(string $type, int $perPage = 30): LengthAwarePaginator
    {
        $paginator = match ($type) {
            'screenshots' => GameScreenshot::query()->with('game.gameList.user')->latest('created_at')->paginate($perPage),
            'avatars' => User::query()->whereNotNull('avatar_path')->latest('updated_at')->paginate($perPage),
            'list-covers' => GameList::query()->with('user')->whereNotNull('cover_path')->latest('updated_at')->paginate($perPage),
            'game-covers' => Game::query()->with('gameList.user')->whereNotNull('cover_path')->latest('updated_at')->paginate($perPage),
            default => abort(404),
        };

        return $paginator
            ->withQueryString()
            ->through(fn (GameScreenshot|User|GameList|Game $model): array => $this->entry($type, $model));
    }

    /** @return array<string, array{bytes: int, formatted: string, percentage: float, percentage_formatted: string}> */
    public function totals(): array
    {
        $totals = ['total' => 0];
        foreach (array_keys(self::TYPES) as $type) {
            $totals[$type] = 0;
        }

        try {
            $disk = Storage::disk('public');
            foreach ($disk->allFiles() as $path) {
                $bytes = $this->size($path) ?? 0;
                $totals['total'] += $bytes;

                foreach (self::TYPES as $type => $metadata) {
                    if (str_starts_with($path, $metadata['directory'].'/')) {
                        $totals[$type] += $bytes;
                        break;
                    }
                }
            }
        } catch (Throwable) {
            // An unavailable disk should not make the admin dashboard unavailable.
        }

        $totalBytes = $totals['total'];

        return collect($totals)
            ->map(function (int $bytes, string $type) use ($totalBytes): array {
                $percentage = $totalBytes > 0
                    ? ($type === 'total' ? 100.0 : round(($bytes / $totalBytes) * 100, 1))
                    : 0.0;

                return [
                    'bytes' => $bytes,
                    'formatted' => $this->formatBytes($bytes),
                    'percentage' => $percentage,
                    'percentage_formatted' => number_format(
                        $percentage,
                        floor($percentage) === $percentage ? 0 : 1,
                        ',',
                        ' ',
                    ).'%',
                ];
            })
            ->all();
    }

    public function downloadPath(string $type, int $id): ?string
    {
        $path = match ($type) {
            'screenshots' => GameScreenshot::query()->find($id)?->path,
            'avatars' => User::query()->find($id)?->avatar_path,
            'list-covers' => GameList::query()->find($id)?->cover_path,
            'game-covers' => Game::query()->find($id)?->cover_path,
            default => null,
        };

        $directory = self::TYPES[$type]['directory'] ?? null;
        if (! $path || ! $directory || ! str_starts_with($path, $directory.'/')) {
            return null;
        }

        return $path;
    }

    /** @return array<string, mixed> */
    private function entry(string $type, GameScreenshot|User|GameList|Game $model): array
    {
        $path = match ($type) {
            'screenshots' => $model->path,
            'avatars' => $model->avatar_path,
            'list-covers', 'game-covers' => $model->cover_path,
        };
        $size = $this->size($path);

        return [
            'id' => $model->id,
            'path' => $path,
            'filename' => basename($path),
            'size' => $size,
            'size_formatted' => $size === null ? 'Файл недоступен' : $this->formatBytes($size),
            'owner' => match ($type) {
                'screenshots' => $model->game?->gameList?->user?->login,
                'avatars' => $model->login,
                'list-covers' => $model->user?->login,
                'game-covers' => $model->gameList?->user?->login,
            },
            'context' => match ($type) {
                'screenshots' => $model->game?->title ?? 'Удалённая игра',
                'avatars' => 'Аватар пользователя',
                'list-covers' => $model->name,
                'game-covers' => $model->title,
            },
            'uploaded_at' => $type === 'screenshots' ? $model->created_at : $model->updated_at,
        ];
    }

    private function size(string $path): ?int
    {
        try {
            $disk = Storage::disk('public');

            return $disk->exists($path) ? $disk->size($path) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        $precision = $unit === 0 ? 0 : ($value >= 10 ? 1 : 2);

        return number_format($value, $precision, ',', ' ').' '.$units[$unit];
    }
}
