<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private const THUMB_SUBDIRECTORY = 'thumbs';
    private const SCOPE_ARTICLES = 'articles';
    private const SCOPE_PROPERTIES = 'properties';
    private const MAX_DIMENSION = 1920;
    private const THUMB_MAX_DIMENSION = 640;
    private const JPEG_QUALITY = 84;
    private const WEBP_QUALITY = 82;
    private const THUMB_WEBP_QUALITY = 78;
    private const THUMB_JPEG_QUALITY = 76;

    public function __construct(
        private string $targetDirectory,
    ) {
    }

    public function upload(UploadedFile $file, string $scope = self::SCOPE_PROPERTIES): string
    {
        $normalizedScope = $this->normalizeScope($scope);
        $targetDirectory = $this->getScopedTargetDirectory($normalizedScope);
        $this->ensureUploadDirectoryIsReady($targetDirectory);

        $mimeType = $file->getMimeType();
        $outputFormat = $this->determineOutputFormat();
        $baseName = $this->generateStorageBaseName();
        $fileName = $baseName . '.' . $outputFormat;
        $targetPath = $targetDirectory . '/' . $fileName;
        $relativePath = $normalizedScope . '/' . $fileName;

        try {
            $file->move($targetDirectory, $fileName);
            $this->optimizeImage($targetPath, $mimeType, $outputFormat);
            $this->createThumbnail($targetPath, $outputFormat);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        return $relativePath;
    }

    /**
     * Post-process an article cover saved by EasyAdmin ImageField (raw PNG/JPEG/WebP on disk).
     * Applies the same resize/WebP conversion and thumbnail generation as {@see upload()}.
     *
     * @param string $relativePathUnderUploads e.g. "articles/foo.png"
     *
     * @return string|null Relative path under uploads (e.g. "articles/foo.webp"), or null if file missing
     */
    public function processStoredArticleCoverImage(string $relativePathUnderUploads): ?string
    {
        $relativePathUnderUploads = trim($relativePathUnderUploads, '/');
        if (!str_starts_with($relativePathUnderUploads, self::SCOPE_ARTICLES . '/')) {
            $relativePathUnderUploads = self::SCOPE_ARTICLES . '/' . basename($relativePathUnderUploads);
        }

        $this->ensureUploadDirectoryIsReady($this->targetDirectory . '/' . self::SCOPE_ARTICLES);

        $fullPath = $this->targetDirectory . '/' . $relativePathUnderUploads;
        if (!is_file($fullPath)) {
            return null;
        }

        $mimeType = mime_content_type($fullPath) ?: '';
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $outputFormat = $this->determineOutputFormat();
        $dir = dirname($fullPath);
        $baseName = pathinfo($fullPath, PATHINFO_FILENAME);
        $canonicalPath = $dir . '/' . $baseName . '.' . $outputFormat;

        $thumbPath = $this->buildThumbnailStoragePath($canonicalPath);
        if ($fullPath === $canonicalPath && $thumbPath !== null && is_file($thumbPath)) {
            return $relativePathUnderUploads;
        }

        if ($fullPath !== $canonicalPath) {
            $oldThumb = $this->buildThumbnailStoragePath($fullPath);
            if ($oldThumb !== null && is_file($oldThumb)) {
                @unlink($oldThumb);
            }
            if (!$this->transformImage($fullPath, $mimeType, $canonicalPath, $outputFormat)) {
                return null;
            }
            @unlink($fullPath);
        } elseif (!$this->transformImage($fullPath, $mimeType, $canonicalPath, $outputFormat)) {
            return null;
        }

        $this->createThumbnail($canonicalPath, $outputFormat);

        return self::SCOPE_ARTICLES . '/' . basename($canonicalPath);
    }

    private function generateStorageBaseName(): string
    {
        try {
            $random = bin2hex(random_bytes(6));
        } catch (\Throwable) {
            $random = uniqid('', true);
        }

        return date('YmdHis') . '-' . str_replace('.', '', $random);
    }

    private function ensureUploadDirectoryIsReady(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Upload directory cannot be created: ' . $directory);
            }
        }

        if (!is_writable($directory)) {
            throw new \RuntimeException('Upload directory is not writable: ' . $directory);
        }

        $thumbDirectory = $directory . '/' . self::THUMB_SUBDIRECTORY;
        if (!is_dir($thumbDirectory)) {
            if (!@mkdir($thumbDirectory, 0775, true) && !is_dir($thumbDirectory)) {
                throw new \RuntimeException('Thumbnail directory cannot be created: ' . $thumbDirectory);
            }
        }

        if (!is_writable($thumbDirectory)) {
            throw new \RuntimeException('Thumbnail directory is not writable: ' . $thumbDirectory);
        }
    }

    private function determineOutputFormat(): string
    {
        // Prefer WebP when GD supports encoding it: better compression with similar visual quality.
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    private function optimizeImage(string $path, string $originalMimeType, string $outputFormat): void
    {
        $this->transformImage($path, $originalMimeType, $path, $outputFormat);
    }

    /**
     * Load, optionally downscale, and encode to WebP or JPEG at $destinationPath.
     */
    private function transformImage(string $sourcePath, string $originalMimeType, string $destinationPath, string $outputFormat): bool
    {
        $image = $this->loadImageResource($sourcePath, $originalMimeType);
        if ($image === false) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $ratio = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($originalMimeType === 'image/png' || $originalMimeType === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $this->writeImageToOutputFormat($image, $destinationPath, $outputFormat);

        return true;
    }

    /**
     * @return \GdImage|false
     */
    private function loadImageResource(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }

    private function writeImageToOutputFormat(\GdImage $image, string $destinationPath, string $outputFormat): void
    {
        if ($outputFormat === 'webp') {
            imagewebp($image, $destinationPath, self::WEBP_QUALITY);
        } else {
            imageinterlace($image, true);
            imagejpeg($image, $destinationPath, self::JPEG_QUALITY);
        }

        imagedestroy($image);
    }

    private function createThumbnail(string $originalPath, string $originalFormat): void
    {
        $source = match ($originalFormat) {
            'webp' => @imagecreatefromwebp($originalPath),
            default => @imagecreatefromjpeg($originalPath),
        };

        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($source);
            return;
        }

        $ratio = min(self::THUMB_MAX_DIMENSION / $width, self::THUMB_MAX_DIMENSION / $height, 1);
        $thumbWidth = max(1, (int) round($width * $ratio));
        $thumbHeight = max(1, (int) round($height * $ratio));

        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        $thumbPath = $this->buildThumbnailStoragePath($originalPath);
        if ($thumbPath !== null) {
            if ($originalFormat === 'webp') {
                imagewebp($thumb, $thumbPath, self::THUMB_WEBP_QUALITY);
            } else {
                imageinterlace($thumb, true);
                imagejpeg($thumb, $thumbPath, self::THUMB_JPEG_QUALITY);
            }
        }

        imagedestroy($thumb);
        imagedestroy($source);
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function buildThumbnailPublicUrl(string $publicUrl): ?string
    {
        if (!str_starts_with($publicUrl, '/uploads/')) {
            return null;
        }

        if (str_contains($publicUrl, '/' . self::THUMB_SUBDIRECTORY . '/')) {
            return $publicUrl;
        }

        $relativePath = ltrim(substr($publicUrl, strlen('/uploads/')), '/');
        if ($relativePath === '') {
            return null;
        }

        $segments = explode('/', $relativePath);
        $fileName = array_pop($segments);
        if (!is_string($fileName) || $fileName === '') {
            return null;
        }

        if ($segments === []) {
            // Backward compatibility for old files in /uploads/<file>
            return '/uploads/' . self::THUMB_SUBDIRECTORY . '/' . $fileName;
        }

        return '/uploads/' . implode('/', $segments) . '/' . self::THUMB_SUBDIRECTORY . '/' . $fileName;
    }

    private function buildThumbnailStoragePath(string $originalPath): ?string
    {
        $baseName = basename($originalPath);
        if ($baseName === '') {
            return null;
        }

        $directory = dirname($originalPath);

        return $directory . '/' . self::THUMB_SUBDIRECTORY . '/' . $baseName;
    }

    private function getScopedTargetDirectory(string $scope): string
    {
        return $this->targetDirectory . '/' . $scope;
    }

    private function normalizeScope(string $scope): string
    {
        $normalized = strtolower(trim($scope));

        return match ($normalized) {
            self::SCOPE_ARTICLES => self::SCOPE_ARTICLES,
            self::SCOPE_PROPERTIES => self::SCOPE_PROPERTIES,
            default => throw new \InvalidArgumentException('Недопустимая область загрузки'),
        };
    }
}
