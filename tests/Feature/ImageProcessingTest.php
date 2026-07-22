<?php

namespace Tests\Feature;

use App\Services\CoverImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageProcessingTest extends TestCase
{
    public function test_phone_photo_orientation_is_normalized_before_resizing(): void
    {
        Storage::fake('public');
        $jpeg = UploadedFile::fake()->image('phone.jpg', 120, 80);
        $orientedJpeg = $this->withExifOrientation(
            file_get_contents($jpeg->getRealPath()),
            6,
        );
        $upload = UploadedFile::fake()->createWithContent('phone.jpg', $orientedJpeg);

        $this->assertSame(6, exif_read_data($upload->getRealPath())['Orientation']);

        $path = $this->app->make(CoverImageService::class)->storeGameCover($upload);

        Storage::disk('public')->assertExists($path);
        $this->assertSame(
            [80, 120],
            array_slice(getimagesizefromstring(Storage::disk('public')->get($path)), 0, 2),
        );
    }

    private function withExifOrientation(string $jpeg, int $orientation): string
    {
        $exif = "Exif\0\0"
            .'MM'
            .pack('n', 0x002A)
            .pack('N', 8)
            .pack('n', 1)
            .pack('n', 0x0112)
            .pack('n', 3)
            .pack('N', 1)
            .pack('n', $orientation)."\0\0"
            .pack('N', 0);
        $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }
}
