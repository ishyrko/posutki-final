<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Infrastructure\Service\FileUploader;
use App\Infrastructure\Service\PropertyImageWatermark;
use PHPUnit\Framework\TestCase;

final class FileUploaderTest extends TestCase
{
    private string $projectDir;
    private string $uploadDir;
    private string $watermarkPath;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/posutki-file-uploader-' . uniqid('', true);
        $this->uploadDir = $this->projectDir . '/public/uploads';
        $this->watermarkPath = $this->projectDir . '/resources/watermark/posutki-by.png';
        mkdir($this->uploadDir . '/properties/thumbs', 0775, true);
        mkdir($this->projectDir . '/var/property-originals', 0775, true);
        mkdir(dirname($this->watermarkPath), 0775, true);

        $this->createWatermarkAsset($this->watermarkPath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testPropertyUploadCreatesOriginalPublicAndCleanThumb(): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            self::markTestSkipped('GD is required.');
        }

        $uploader = $this->createUploader();
        $sourcePath = $this->createFixtureImage(800, 600, 'jpeg');
        $uploadedFile = $this->createUploadedFile($sourcePath, 'photo.jpg', 'image/jpeg');

        $relativePath = $uploader->upload($uploadedFile, FileUploader::SCOPE_PROPERTIES);
        $baseName = pathinfo($relativePath, PATHINFO_FILENAME);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $originalPath = $this->projectDir . '/var/property-originals/' . $baseName . '.' . $extension;
        $publicPath = $this->uploadDir . '/properties/' . $baseName . '.' . $extension;
        $thumbPath = $this->uploadDir . '/properties/thumbs/' . $baseName . '.' . $extension;

        self::assertFileExists($originalPath);
        self::assertFileExists($publicPath);
        self::assertFileExists($thumbPath);
        self::assertNotSame(filesize($originalPath), filesize($publicPath));
        self::assertLessThan(filesize($originalPath), filesize($thumbPath));
    }

    public function testAvatarUploadDoesNotCreatePropertyOriginal(): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            self::markTestSkipped('GD is required.');
        }

        mkdir($this->uploadDir . '/avatars/thumbs', 0775, true);
        $uploader = $this->createUploader();
        $sourcePath = $this->createFixtureImage(400, 400, 'jpeg');
        $uploadedFile = $this->createUploadedFile($sourcePath, 'avatar.jpg', 'image/jpeg');

        $relativePath = $uploader->upload($uploadedFile, 'avatars');
        $publicPath = $this->uploadDir . '/' . $relativePath;

        self::assertFileExists($publicPath);
        self::assertSame([], glob($this->projectDir . '/var/property-originals/*') ?: []);
    }

    public function testRotatePropertyImageUsesOriginalWithoutDuplicatingWatermark(): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            self::markTestSkipped('GD is required.');
        }

        $uploader = $this->createUploader();
        $sourcePath = $this->createFixtureImage(900, 500, 'jpeg');
        $uploadedFile = $this->createUploadedFile($sourcePath, 'photo.jpg', 'image/jpeg');
        $relativePath = $uploader->upload($uploadedFile, FileUploader::SCOPE_PROPERTIES);
        $initialUrl = '/uploads/' . $relativePath;

        $rotated = $uploader->rotatePropertyImage($initialUrl);
        $newBaseName = pathinfo($rotated['url'], PATHINFO_FILENAME);
        $extension = pathinfo($rotated['url'], PATHINFO_EXTENSION);
        $newOriginalPath = $this->projectDir . '/var/property-originals/' . $newBaseName . '.' . $extension;
        $newPublicPath = $this->uploadDir . '/properties/' . $newBaseName . '.' . $extension;
        $newThumbPath = $this->uploadDir . '/properties/thumbs/' . $newBaseName . '.' . $extension;

        self::assertNotSame($initialUrl, $rotated['url']);
        self::assertFileExists($newOriginalPath);
        self::assertFileExists($newPublicPath);
        self::assertFileExists($newThumbPath);

        [$originalWidth, $originalHeight] = getimagesize($newOriginalPath);
        [$publicWidth, $publicHeight] = getimagesize($newPublicPath);
        self::assertSame($originalWidth, $publicWidth);
        self::assertSame($originalHeight, $publicHeight);
        self::assertGreaterThan($originalWidth, $originalHeight);
    }

    public function testProcessExistingPropertyImageConvertsLegacyImageWhenWebpAvailable(): void
    {
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) {
            self::markTestSkipped('GD with WebP support is required for conversion test.');
        }

        $uploader = $this->createUploader();
        $legacyPath = $this->uploadDir . '/properties/legacy-photo.jpg';
        $this->createFixtureImage(640, 480, 'jpeg', $legacyPath);

        $result = $uploader->processExistingPropertyImage('/uploads/properties/legacy-photo.jpg');

        self::assertNotNull($result);
        self::assertSame('/uploads/properties/legacy-photo.jpg', $result['oldUrl']);
        self::assertSame('/uploads/properties/legacy-photo.webp', $result['newUrl']);
        self::assertFileExists($this->uploadDir . '/properties/legacy-photo.webp');
        self::assertFileExists($this->uploadDir . '/properties/thumbs/legacy-photo.webp');
        self::assertFileExists($this->projectDir . '/var/property-originals/legacy-photo.webp');
        self::assertFileDoesNotExist($legacyPath);
    }

    public function testProcessExistingPropertyImageWatermarksLegacyJpegWithoutWebp(): void
    {
        if (!function_exists('imagecreatefromjpeg') || function_exists('imagewebp')) {
            self::markTestSkipped('This fallback test requires GD without WebP encoding.');
        }

        $uploader = $this->createUploader();
        $legacyPath = $this->uploadDir . '/properties/legacy-photo.jpg';
        $this->createFixtureImage(640, 480, 'jpeg', $legacyPath);

        $result = $uploader->processExistingPropertyImage('/uploads/properties/legacy-photo.jpg');

        self::assertNull($result);
        self::assertFileExists($legacyPath);
        self::assertFileExists($this->projectDir . '/var/property-originals/legacy-photo.jpg');
        self::assertFileExists($this->uploadDir . '/properties/thumbs/legacy-photo.jpg');
        self::assertNotSame(
            file_get_contents($this->projectDir . '/var/property-originals/legacy-photo.jpg'),
            file_get_contents($legacyPath),
        );
    }

    private function createUploader(): FileUploader
    {
        return new FileUploader(
            $this->uploadDir,
            $this->projectDir,
            new PropertyImageWatermark($this->watermarkPath),
        );
    }

    private function createUploadedFile(string $path, string $originalName, string $mimeType): \Symfony\Component\HttpFoundation\File\UploadedFile
    {
        return new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $path,
            $originalName,
            $mimeType,
            null,
            true,
        );
    }

    private function createFixtureImage(int $width, int $height, string $format, ?string $targetPath = null): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 120, 80, 40);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);
        $accent = imagecolorallocate($image, 210, 180, 140);
        imagefilledrectangle($image, (int) ($width * 0.2), (int) ($height * 0.2), (int) ($width * 0.8), (int) ($height * 0.8), $accent);

        $targetPath ??= sys_get_temp_dir() . '/fixture-' . uniqid('', true) . '.' . $format;

        if ($format === 'jpeg') {
            imagejpeg($image, $targetPath, 90);
        } else {
            imagepng($image, $targetPath);
        }

        imagedestroy($image);

        return $targetPath;
    }

    private function createWatermarkAsset(string $path): void
    {
        $image = imagecreatetruecolor(120, 30);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        $white = imagecolorallocatealpha($image, 255, 255, 255, 40);
        imagefilledrectangle($image, 4, 4, 116, 26, $white);
        imagepng($image, $path);
        imagedestroy($image);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
