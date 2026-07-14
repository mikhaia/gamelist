<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CoverImageService
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    public function storeUpload(
        UploadedFile $file,
        ?string $oldPath = null,
        string $directory = 'game-covers',
        int $maxWidth = 900,
        int $maxHeight = 1200,
    ): string {
        return $this->storeBytes($file->get(), $oldPath, $directory, $maxWidth, $maxHeight);
    }

    public function storeUrl(string $url, ?string $oldPath = null): string
    {
        $response = null;

        for ($redirects = 0; $redirects < 4; $redirects++) {
            $this->assertSafeUrl($url);
            $response = Http::timeout(12)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['User-Agent' => 'GameList/1.0'])
                ->get($url);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if (! $location) {
                    break;
                }
                $url = $this->absoluteRedirectUrl($url, $location);

                continue;
            }

            break;
        }

        if (! $response?->successful()) {
            throw ValidationException::withMessages(['cover_url' => __('app.errors.cover_download')]);
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (! str_starts_with($contentType, 'image/')) {
            throw ValidationException::withMessages(['cover_url' => __('app.errors.cover_not_image')]);
        }

        return $this->storeBytes($response->body(), $oldPath, 'game-covers', 900, 1200);
    }

    private function storeBytes(
        string $bytes,
        ?string $oldPath,
        string $directory,
        int $maxWidth,
        int $maxHeight,
    ): string {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['cover' => __('app.errors.cover_size')]);
        }

        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            throw ValidationException::withMessages(['cover' => __('app.errors.cover_invalid')]);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxWidth / $width, $maxHeight / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagewebp($target, null, 82);
        $webp = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $webp);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            throw ValidationException::withMessages(['cover_url' => __('app.errors.cover_url')]);
        }

        $records = dns_get_record($parts['host'], DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            throw ValidationException::withMessages(['cover_url' => __('app.errors.cover_url')]);
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (! $ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw ValidationException::withMessages(['cover_url' => __('app.errors.cover_url')]);
            }
        }
    }

    private function absoluteRedirectUrl(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        return $origin.'/'.ltrim($location, '/');
    }
}
