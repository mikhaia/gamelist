<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AdminErrorLogService
{
    /** @var array<string, string> */
    private const LEVELS = [
        'warning' => 'Предупреждение',
        'error' => 'Ошибка',
        'critical' => 'Критическая',
        'alert' => 'Тревога',
        'emergency' => 'Авария',
    ];

    /** @return array<string, string> */
    public function levels(): array
    {
        return self::LEVELS;
    }

    /**
     * @return array{
     *     entries: array<int, array<string, mixed>>,
     *     files: array<int, array<string, mixed>>,
     *     stats: array<string, int>,
     *     truncated: bool
     * }
     */
    public function read(?string $level = null, ?string $search = null): array
    {
        $files = $this->logFiles();
        $entries = [];
        $truncated = false;

        foreach ($files as $file) {
            [$content, $fileTruncated] = $this->readTail($file['path']);
            $truncated = $truncated || $fileTruncated;
            $entries = [...$entries, ...$this->parse($content, $file['name'])];
        }

        usort($entries, fn (array $left, array $right): int => [$right['timestamp'], $right['sequence']] <=> [$left['timestamp'], $left['sequence']]);

        $maxEntries = max(100, (int) config('logging.admin_viewer.max_entries', 2000));
        if (count($entries) > $maxEntries) {
            $entries = array_slice($entries, 0, $maxEntries);
            $truncated = true;
        }

        $stats = $this->stats($entries, count($files));
        $normalizedSearch = Str::lower(trim((string) $search));
        $entries = array_values(array_filter($entries, function (array $entry) use ($level, $normalizedSearch): bool {
            if ($level && $entry['level'] !== $level) {
                return false;
            }

            return $normalizedSearch === ''
                || Str::contains(Str::lower($entry['message']."\n".$entry['details']), $normalizedSearch);
        }));

        return compact('entries', 'files', 'stats', 'truncated');
    }

    /** @return array<int, array{name: string, path: string, size: int, size_formatted: string, updated_at: Carbon|null}> */
    private function logFiles(): array
    {
        $directory = (string) config('logging.admin_viewer.path', storage_path('logs'));
        $realDirectory = realpath($directory);
        if ($realDirectory === false || ! is_dir($realDirectory)) {
            return [];
        }

        $paths = glob($realDirectory.DIRECTORY_SEPARATOR.'laravel*.log') ?: [];
        $paths = array_values(array_filter($paths, function (string $path) use ($realDirectory): bool {
            $realPath = realpath($path);

            return $realPath !== false
                && str_starts_with($realPath, $realDirectory.DIRECTORY_SEPARATOR)
                && is_file($realPath)
                && is_readable($realPath);
        }));
        usort($paths, fn (string $left, string $right): int => ((int) filemtime($right)) <=> ((int) filemtime($left)));
        $paths = array_slice($paths, 0, max(1, (int) config('logging.admin_viewer.max_files', 14)));

        return array_map(function (string $path): array {
            $size = max(0, (int) filesize($path));
            $modifiedAt = filemtime($path);

            return [
                'name' => basename($path),
                'path' => $path,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'updated_at' => $modifiedAt ? Carbon::createFromTimestamp($modifiedAt) : null,
            ];
        }, $paths);
    }

    /** @return array{string, bool} */
    private function readTail(string $path): array
    {
        $size = max(0, (int) filesize($path));
        $maxBytes = max(262144, (int) config('logging.admin_viewer.max_bytes_per_file', 4194304));
        $offset = max(0, $size - $maxBytes);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return ['', false];
        }

        try {
            if ($offset > 0) {
                fseek($handle, $offset);
                fgets($handle);
            }

            return [(string) stream_get_contents($handle), $offset > 0];
        } finally {
            fclose($handle);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function parse(string $content, string $file): array
    {
        if ($content === '') {
            return [];
        }

        $entries = [];
        $current = null;
        $sequence = 0;

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (preg_match('/^\[(?<datetime>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?<environment>[^.]+)\.(?<level>[A-Z]+): (?<message>.*)$/', $line, $matches)) {
                if ($current !== null) {
                    $entry = $this->entry($current, $file, $sequence++);
                    if ($entry !== null) {
                        $entries[] = $entry;
                    }
                }

                $current = [
                    'datetime' => $matches['datetime'],
                    'environment' => $matches['environment'],
                    'level' => Str::lower($matches['level']),
                    'message' => $matches['message'],
                    'raw' => $line,
                ];

                continue;
            }

            if ($current !== null) {
                $current['raw'] .= "\n".$line;
            }
        }

        if ($current !== null) {
            $entry = $this->entry($current, $file, $sequence);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /** @param array{datetime: string, environment: string, level: string, message: string, raw: string} $record */
    private function entry(array $record, string $file, int $sequence): ?array
    {
        if (! array_key_exists($record['level'], self::LEVELS)) {
            return null;
        }

        $timestamp = strtotime($record['datetime']) ?: 0;
        $message = $this->sanitize($record['message']);
        $contextPosition = strpos($message, ' {"');
        if ($contextPosition !== false) {
            $message = substr($message, 0, $contextPosition);
        }
        $message = Str::limit(trim($message), 280, '…');

        return [
            'id' => hash('sha256', $file.'|'.$record['datetime'].'|'.$record['message'].'|'.$sequence),
            'datetime' => $record['datetime'],
            'timestamp' => $timestamp,
            'environment' => $record['environment'],
            'level' => $record['level'],
            'level_label' => self::LEVELS[$record['level']],
            'message' => $message !== '' ? $message : 'Ошибка без сообщения',
            'details' => $this->sanitize($record['raw']),
            'file' => $file,
            'sequence' => $sequence,
        ];
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function stats(array $entries, int $filesCount): array
    {
        $today = now()->startOfDay()->getTimestamp();
        $sevenDaysAgo = now()->subDays(7)->getTimestamp();

        return [
            'total' => count($entries),
            'today' => count(array_filter($entries, fn (array $entry): bool => $entry['timestamp'] >= $today)),
            'seven_days' => count(array_filter($entries, fn (array $entry): bool => $entry['timestamp'] >= $sevenDaysAgo)),
            'critical' => count(array_filter($entries, fn (array $entry): bool => in_array($entry['level'], ['critical', 'alert', 'emergency'], true))),
            'files' => $filesCount,
        ];
    }

    private function sanitize(string $value): string
    {
        $value = str_replace(
            [base_path(), storage_path()],
            ['[project]', '[storage]'],
            $value,
        );

        $patterns = [
            '/("(?:password|password_confirmation|authorization|cookie|token|access_token|api[_-]?key|secret)"\s*:\s*")[^"]*(")/i' => '$1[REDACTED]$2',
            "/('(?:password|password_confirmation|authorization|cookie|token|access_token|api[_-]?key|secret)'\s*=>\s*')[^']*(')/i" => '$1[REDACTED]$2',
            '/([?&](?:key|api_key|token|access_token)=)[^&\s"\']+/i' => '$1[REDACTED]',
            '/(Bearer\s+)[A-Za-z0-9._~+\/=\-]+/i' => '$1[REDACTED]',
            '#(https?://[^:/\s]+:)[^@\s]+@#i' => '$1[REDACTED]@',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $value) ?? $value;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return number_format($bytes, 0, ',', ' ').' Б';
        }

        $units = ['КБ', 'МБ', 'ГБ', 'ТБ'];
        $value = $bytes / 1024;
        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'ТБ') {
                return number_format($value, 2, ',', ' ').' '.$unit;
            }
            $value /= 1024;
        }

        return number_format($value, 2, ',', ' ').' ТБ';
    }
}
