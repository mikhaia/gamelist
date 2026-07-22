<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;

class CoverImageService
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    private const WEBP_QUALITY = 82;

    public function __construct(private readonly ImageManagerInterface $images) {}

    public function storeAvatar(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->storeUpload($file, $oldPath, 'avatars', 256, 256, true, 'avatar');
    }

    public function storeProfileCover(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->storeUpload($file, $oldPath, 'profile-covers', 2432, 1400, false, 'profile_cover');
    }

    public function storeListCover(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->storeUpload($file, $oldPath, 'list-covers', 1800, 1200);
    }

    public function storeGameCover(UploadedFile $file, ?string $oldPath = null): string
    {
        return $this->storeUpload($file, $oldPath, 'game-covers', 900, 1200);
    }

    public function storeScreenshot(UploadedFile $file): string
    {
        return $this->storeUpload($file, null, 'game-screenshots', 1920, 1080, false, 'screenshots');
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

        return $this->storeBytes($response->body(), $oldPath, 'game-covers', 900, 1200, 'cover_url');
    }

    private function storeUpload(
        UploadedFile $file,
        ?string $oldPath,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        bool $cover = false,
        string $errorKey = 'cover',
    ): string {
        if (($file->getSize() ?: 0) > self::MAX_BYTES) {
            throw ValidationException::withMessages([$errorKey => __('app.errors.cover_size')]);
        }

        return $this->storeImage(
            fn (): ImageInterface => $this->images->decode($file),
            $oldPath,
            $directory,
            $maxWidth,
            $maxHeight,
            $cover,
            $errorKey,
        );
    }

    private function storeBytes(
        string $bytes,
        ?string $oldPath,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        string $errorKey = 'cover',
    ): string {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw ValidationException::withMessages([$errorKey => __('app.errors.cover_size')]);
        }

        return $this->storeImage(
            fn (): ImageInterface => $this->images->decodeBinary($bytes),
            $oldPath,
            $directory,
            $maxWidth,
            $maxHeight,
            false,
            $errorKey,
        );
    }

    /** @param callable(): ImageInterface $decode */
    private function storeImage(
        callable $decode,
        ?string $oldPath,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        bool $cover,
        string $errorKey,
    ): string {
        try {
            $image = $decode();
            $cover
                ? $image->cover($maxWidth, $maxHeight)
                : $image->scaleDown($maxWidth, $maxHeight);
            $webp = $image->encodeUsingFormat(Format::WEBP, quality: self::WEBP_QUALITY, strip: true);
        } catch (ImageException) {
            throw ValidationException::withMessages([$errorKey => __('app.errors.cover_invalid')]);
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, (string) $webp);

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
