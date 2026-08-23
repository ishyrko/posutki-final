<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private const THUMB_SUBDIRECTORY = 'thumbs';
    private const SCOPE_ARTICLES = 'articles';
    private const SCOPE_AVATARS = 'avatars';
    public const SCOPE_PROPERTIES = 'properties';
    private const SCOPE_STATIC_PAGES = 'static-pages';
    private const SCOPE_LANDMARKS = 'landmarks';
    private const PROPERTY_ORIGINALS_SUBDIRECTORY = 'property-originals';
    private const MAX_DIMENSION = 1920;
    private const CONTENT_MAX_DIMENSION = 2560;
    private const THUMB_MAX_DIMENSION = 640;
    private const AVATAR_MAX_DIMENSION = 256;
    private const AVATAR_THUMB_MAX_DIMENSION = 96;
    private const JPEG_QUALITY = 84;
    private const WEBP_QUALITY = 82;
    private const CONTENT_JPEG_QUALITY = 92;
    private const CONTENT_WEBP_QUALITY = 92;
    private const THUMB_WEBP_QUALITY = 78;
    private const THUMB_JPEG_QUALITY = 76;

    public function __construct(
        private string $targetDirectory,
        private string $projectDirectory,
        private readonly PropertyImageWatermark $propertyImageWatermark,
    ) {
    }

    public function upload(UploadedFile $file, string $scope = self::SCOPE_PROPERTIES): string
    {
        $normalizedScope = $this->normalizeScope($scope);

        if ($normalizedScope === self::SCOPE_PROPERTIES) {
            return $this->uploadPropertyImage($file);
        }

        return $this->uploadGenericImage($file, $normalizedScope);
    }

    /**
     * @return array{url: string, thumbnailUrl: string|null}
     */
    public function rotatePropertyImage(string $publicUrl): array
    {
        $publicPath = $this->resolvePublicUploadPath($publicUrl);
        if ($publicPath === null || !is_file($publicPath)) {
            throw new \InvalidArgumentException('Файл изображения не найден');
        }

        $currentBaseName = pathinfo($publicPath, PATHINFO_FILENAME);
        $outputFormat = $this->determineOutputFormat(self::SCOPE_PROPERTIES, 'image/webp');
        $originalPath = $this->resolvePropertyOriginalPath($currentBaseName, $outputFormat);

        if (!is_file($originalPath)) {
            $originalPath = $this->bootstrapPropertyOriginalFromPublic($publicPath, $currentBaseName, $outputFormat);
        }

        $rotatedOriginal = $this->rotateImageFile($originalPath);
        if ($rotatedOriginal === false) {
            throw new \RuntimeException('Не удалось повернуть изображение');
        }

        $newBaseName = $this->generateStorageBaseName();
        $newOriginalPath = $this->resolvePropertyOriginalPath($newBaseName, $outputFormat);
        $this->writeImageResource($rotatedOriginal, $newOriginalPath, $outputFormat, self::SCOPE_PROPERTIES);
        imagedestroy($rotatedOriginal);

        $propertiesDirectory = $this->getScopedTargetDirectory(self::SCOPE_PROPERTIES);
        $newPublicPath = $propertiesDirectory . '/' . $newBaseName . '.' . $outputFormat;
        $this->publishWatermarkedPropertyImage($newOriginalPath, $newPublicPath, $outputFormat);
        $this->createThumbnail(
            $newOriginalPath,
            $outputFormat,
            self::THUMB_MAX_DIMENSION,
            $propertiesDirectory . '/' . self::THUMB_SUBDIRECTORY,
        );

        $newUrl = '/uploads/' . self::SCOPE_PROPERTIES . '/' . $newBaseName . '.' . $outputFormat;

        return [
            'url' => $newUrl,
            'thumbnailUrl' => $this->buildThumbnailPublicUrl($newUrl),
        ];
    }

    /**
     * @return array{oldUrl: string, newUrl: string}|null null = URL не менялся (файл уже нужного формата), но штамп мог быть обновлён
     */
    public function processExistingPropertyImage(string $publicUrl, bool $rebuildOriginal = false): ?array
    {
        if (!$this->isLocalPropertyImageUrl($publicUrl)) {
            return null;
        }

        $publicPath = $this->resolvePublicUploadPath($publicUrl);
        if ($publicPath === null || !is_file($publicPath)) {
            return null;
        }

        $oldUrl = $this->normalizePublicUploadUrl($publicUrl);
        $baseName = pathinfo($publicPath, PATHINFO_FILENAME);
        $sourceMimeType = mime_content_type($publicPath) ?: $this->guessMimeTypeFromPath($publicPath);
        $outputFormat = $this->determineOutputFormat(self::SCOPE_PROPERTIES, $sourceMimeType);
        $propertiesDirectory = $this->getScopedTargetDirectory(self::SCOPE_PROPERTIES);
        $this->ensureUploadDirectoryIsReady($propertiesDirectory);
        $this->ensurePropertyOriginalsDirectoryIsReady();

        $originalPath = $this->resolvePropertyOriginalPath($baseName, $outputFormat);

        if ($rebuildOriginal) {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $legacyExt) {
                $legacyOriginal = $this->resolvePropertyOriginalPath($baseName, $legacyExt);
                if (is_file($legacyOriginal)) {
                    @unlink($legacyOriginal);
                }
            }
        }

        if ($rebuildOriginal || !is_file($originalPath)) {
            $originalPath = $this->bootstrapPropertyOriginalFromPublic($publicPath, $baseName, $outputFormat, $sourceMimeType);
        } elseif ($outputFormat === 'webp' && !str_ends_with(strtolower($originalPath), '.webp')) {
            $convertedOriginalPath = $this->resolvePropertyOriginalPath($baseName, 'webp');
            if (!$this->transformImage($originalPath, $this->guessMimeTypeFromPath($originalPath), $convertedOriginalPath, 'webp', self::MAX_DIMENSION, self::SCOPE_PROPERTIES)) {
                throw new \RuntimeException('Failed to convert property original to WebP: ' . $originalPath);
            }
            if ($convertedOriginalPath !== $originalPath && is_file($originalPath)) {
                @unlink($originalPath);
            }
            $originalPath = $convertedOriginalPath;
        }

        $newBaseName = $outputFormat === 'webp' && !str_ends_with(strtolower($publicPath), '.webp')
            ? $baseName
            : $baseName;
        $newPublicPath = $propertiesDirectory . '/' . $newBaseName . '.' . $outputFormat;

        if ($publicPath !== $newPublicPath && is_file($newPublicPath)) {
            @unlink($newPublicPath);
        }

        $this->publishWatermarkedPropertyImage($originalPath, $newPublicPath, $outputFormat);

        $thumbDirectory = $propertiesDirectory . '/' . self::THUMB_SUBDIRECTORY;
        $this->createThumbnail($originalPath, $outputFormat, self::THUMB_MAX_DIMENSION, $thumbDirectory);

        if ($publicPath !== $newPublicPath && is_file($publicPath)) {
            $oldThumbPath = $this->buildThumbnailStoragePath($publicPath);
            @unlink($publicPath);
            if ($oldThumbPath !== null && is_file($oldThumbPath)) {
                @unlink($oldThumbPath);
            }
        }

        $newUrl = '/uploads/' . self::SCOPE_PROPERTIES . '/' . $newBaseName . '.' . $outputFormat;

        if ($newUrl === $oldUrl) {
            return null;
        }

        return [
            'oldUrl' => $oldUrl,
            'newUrl' => $newUrl,
        ];
    }

    public function isLocalPropertyImageUrl(string $publicUrl): bool
    {
        $normalized = $this->normalizePublicUploadUrl($publicUrl);

        return str_starts_with($normalized, '/uploads/properties/')
            && !str_contains($normalized, '/thumbs/')
            && !str_starts_with($normalized, 'http');
    }

    public function getPropertyOriginalsDirectory(): string
    {
        return $this->projectDirectory . '/var/' . self::PROPERTY_ORIGINALS_SUBDIRECTORY;
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
        return $this->processStoredContentScopeImage($relativePathUnderUploads, self::SCOPE_ARTICLES);
    }

    /**
     * Post-process a landmark image saved by EasyAdmin ImageField.
     *
     * @param string $relativePathUnderUploads e.g. "landmarks/foo.png"
     *
     * @return string|null Relative path under uploads (e.g. "landmarks/foo.webp"), or null if file missing
     */
    public function processStoredLandmarkImage(string $relativePathUnderUploads): ?string
    {
        return $this->processStoredContentScopeImage($relativePathUnderUploads, self::SCOPE_LANDMARKS);
    }

    private function uploadPropertyImage(UploadedFile $file): string
    {
        $propertiesDirectory = $this->getScopedTargetDirectory(self::SCOPE_PROPERTIES);
        $this->ensureUploadDirectoryIsReady($propertiesDirectory);
        $this->ensurePropertyOriginalsDirectoryIsReady();

        $mimeType = (string) $file->getMimeType();
        $outputFormat = $this->determineOutputFormat(self::SCOPE_PROPERTIES, $mimeType);
        $baseName = $this->generateStorageBaseName();
        $tempFileName = '.tmp-' . $baseName . '.' . $outputFormat;

        try {
            $file->move($propertiesDirectory, $tempFileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        $tempPath = $propertiesDirectory . '/' . $tempFileName;
        $originalPath = $this->resolvePropertyOriginalPath($baseName, $outputFormat);
        $publicPath = $propertiesDirectory . '/' . $baseName . '.' . $outputFormat;

        if (!$this->transformImage($tempPath, $mimeType, $originalPath, $outputFormat, self::MAX_DIMENSION, self::SCOPE_PROPERTIES)) {
            @unlink($tempPath);
            throw new \RuntimeException('Failed to process uploaded property image');
        }

        @unlink($tempPath);
        $this->publishWatermarkedPropertyImage($originalPath, $publicPath, $outputFormat);
        $this->createThumbnail(
            $originalPath,
            $outputFormat,
            self::THUMB_MAX_DIMENSION,
            $propertiesDirectory . '/' . self::THUMB_SUBDIRECTORY,
        );

        return self::SCOPE_PROPERTIES . '/' . $baseName . '.' . $outputFormat;
    }

    private function uploadGenericImage(UploadedFile $file, string $normalizedScope): string
    {
        $targetDirectory = $this->getScopedTargetDirectory($normalizedScope);
        $this->ensureUploadDirectoryIsReady($targetDirectory);

        $mimeType = (string) $file->getMimeType();
        $outputFormat = $this->determineOutputFormat($normalizedScope, $mimeType);
        $baseName = $this->generateStorageBaseName();
        $fileName = $baseName . '.' . $outputFormat;
        $targetPath = $targetDirectory . '/' . $fileName;
        $relativePath = $normalizedScope . '/' . $fileName;

        try {
            $file->move($targetDirectory, $fileName);
            $maxDimension = $this->getMaxDimensionForScope($normalizedScope);
            $thumbMaxDimension = $this->getThumbMaxDimensionForScope($normalizedScope);
            if ($this->canUseOriginalWithoutReencode($targetPath, $mimeType, $outputFormat, $maxDimension, $normalizedScope)) {
                $this->createThumbnail($targetPath, $outputFormat, $thumbMaxDimension);
            } else {
                $this->optimizeImage($targetPath, $mimeType, $outputFormat, $maxDimension, $normalizedScope);
                $this->createThumbnail($targetPath, $outputFormat, $thumbMaxDimension);
            }
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        return $relativePath;
    }

    /**
     * @param string $relativePathUnderUploads e.g. "articles/foo.png"
     *
     * @return string|null Relative path under uploads (e.g. "articles/foo.webp"), or null if file missing
     */
    private function processStoredContentScopeImage(string $relativePathUnderUploads, string $scope): ?string
    {
        $relativePathUnderUploads = trim($relativePathUnderUploads, '/');
        if (!str_starts_with($relativePathUnderUploads, $scope . '/')) {
            $relativePathUnderUploads = $scope . '/' . basename($relativePathUnderUploads);
        }

        $this->ensureUploadDirectoryIsReady($this->targetDirectory . '/' . $scope);

        $fullPath = $this->targetDirectory . '/' . $relativePathUnderUploads;
        if (!is_file($fullPath)) {
            return null;
        }

        $mimeType = mime_content_type($fullPath) ?: '';
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $outputFormat = $this->determineOutputFormat($scope, $mimeType);
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
            if (!$this->transformImage($fullPath, $mimeType, $canonicalPath, $outputFormat, self::CONTENT_MAX_DIMENSION, $scope)) {
                return null;
            }
            @unlink($fullPath);
        } elseif (!$this->transformImage($fullPath, $mimeType, $canonicalPath, $outputFormat, self::CONTENT_MAX_DIMENSION, $scope)) {
            return null;
        }

        $this->createThumbnail($canonicalPath, $outputFormat);

        return $scope . '/' . basename($canonicalPath);
    }

    private function publishWatermarkedPropertyImage(string $originalPath, string $publicPath, string $outputFormat): void
    {
        if (!is_file($originalPath)) {
            throw new \RuntimeException('Property original image is missing: ' . $originalPath);
        }

        $directory = dirname($publicPath);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Upload directory cannot be created: ' . $directory);
        }

        try {
            if (!$this->propertyImageWatermark->applyToFile($originalPath, $publicPath, $outputFormat)) {
                throw new \RuntimeException(sprintf(
                    'Failed to apply watermark to property image (%s -> %s, format=%s)',
                    $originalPath,
                    $publicPath,
                    $outputFormat,
                ));
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf(
                'Failed to apply watermark to property image (%s -> %s, format=%s): %s',
                $originalPath,
                $publicPath,
                $outputFormat,
                $e->getMessage(),
            ), 0, $e);
        }
    }

    private function bootstrapPropertyOriginalFromPublic(
        string $publicPath,
        string $baseName,
        string $outputFormat,
        ?string $sourceMimeType = null,
    ): string {
        $sourceMimeType ??= mime_content_type($publicPath) ?: $this->guessMimeTypeFromPath($publicPath);
        $originalPath = $this->resolvePropertyOriginalPath($baseName, $outputFormat);

        if (!$this->transformImage($publicPath, $sourceMimeType, $originalPath, $outputFormat, self::MAX_DIMENSION, self::SCOPE_PROPERTIES)) {
            throw new \RuntimeException('Failed to bootstrap property original image');
        }

        return $originalPath;
    }

    private function resolvePropertyOriginalPath(string $baseName, string $outputFormat): string
    {
        return $this->getPropertyOriginalsDirectory() . '/' . $baseName . '.' . $outputFormat;
    }

    private function ensurePropertyOriginalsDirectoryIsReady(): void
    {
        $directory = $this->getPropertyOriginalsDirectory();
        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException('Property originals directory cannot be created: ' . $directory);
            }
        }

        if (!is_writable($directory)) {
            throw new \RuntimeException('Property originals directory is not writable: ' . $directory);
        }
    }

    private function resolvePublicUploadPath(string $publicUrl): ?string
    {
        $normalized = $this->normalizePublicUploadUrl($publicUrl);
        if (!str_starts_with($normalized, '/uploads/')) {
            return null;
        }

        $relativePath = ltrim(substr($normalized, strlen('/uploads/')), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        return $this->targetDirectory . '/' . $relativePath;
    }

    private function normalizePublicUploadUrl(string $publicUrl): string
    {
        $publicUrl = trim($publicUrl);
        if (str_starts_with($publicUrl, 'http://') || str_starts_with($publicUrl, 'https://')) {
            $path = parse_url($publicUrl, PHP_URL_PATH);
            if (!is_string($path) || $path === '') {
                return $publicUrl;
            }

            return $path;
        }

        if (!str_starts_with($publicUrl, '/')) {
            return '/' . ltrim($publicUrl, '/');
        }

        return $publicUrl;
    }

    /**
     * @return \GdImage|false
     */
    private function rotateImageFile(string $path): \GdImage|false
    {
        $mimeType = mime_content_type($path) ?: $this->guessMimeTypeFromPath($path);
        $image = $this->loadImageResource($path, $mimeType);
        if ($image === false) {
            return false;
        }

        if (function_exists('imagerotate')) {
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            $rotated = imagerotate($image, -90, $transparent);
            imagedestroy($image);

            if ($rotated === false) {
                return false;
            }

            imagealphablending($rotated, false);
            imagesavealpha($rotated, true);

            return $rotated;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $rotated = imagecreatetruecolor($height, $width);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                imagesetpixel($rotated, $height - 1 - $y, $x, $color);
            }
        }

        imagedestroy($image);

        return $rotated;
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

    private function determineOutputFormat(string $scope, string $originalMimeType): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    private function optimizeImage(
        string $path,
        string $originalMimeType,
        string $outputFormat,
        int $maxDimension = self::MAX_DIMENSION,
        string $scope = self::SCOPE_PROPERTIES,
    ): void {
        $this->transformImage($path, $originalMimeType, $path, $outputFormat, $maxDimension, $scope);
    }

    private function canUseOriginalWithoutReencode(
        string $path,
        string $originalMimeType,
        string $outputFormat,
        int $maxDimension,
        string $scope = self::SCOPE_PROPERTIES,
    ): bool {
        if ($scope === self::SCOPE_PROPERTIES) {
            return false;
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            return false;
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        if ($width <= 0 || $height <= 0 || $width > $maxDimension || $height > $maxDimension) {
            return false;
        }

        return match ($outputFormat) {
            'webp' => $originalMimeType === 'image/webp',
            'jpg' => $originalMimeType === 'image/jpeg',
            default => false,
        };
    }

    /**
     * Load, optionally downscale, and encode to WebP or JPEG at $destinationPath.
     */
    private function transformImage(
        string $sourcePath,
        string $originalMimeType,
        string $destinationPath,
        string $outputFormat,
        int $maxDimension = self::MAX_DIMENSION,
        string $scope = self::SCOPE_PROPERTIES,
    ): bool {
        $image = $this->loadImageResource($sourcePath, $originalMimeType);
        if ($image === false) {
            return false;
        }

        $image = $this->resizeImageResource($image, $originalMimeType, $maxDimension);
        if ($image === false) {
            return false;
        }

        $this->writeImageResource($image, $destinationPath, $outputFormat, $scope);

        return true;
    }

    /**
     * @return \GdImage|false
     */
    private function resizeImageResource(\GdImage $image, string $originalMimeType, int $maxDimension): \GdImage|false
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($originalMimeType === 'image/png' || $originalMimeType === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);

            return $resized;
        }

        if ($originalMimeType === 'image/png' || $originalMimeType === 'image/webp') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    /**
     * @return \GdImage|false
     */
    private function loadImageResource(string $path, string $mimeType)
    {
        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            $mimeType = $this->guessMimeTypeFromPath($path);
        }

        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function guessMimeTypeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => '',
        };
    }

    private function writeImageResource(
        \GdImage $image,
        string $destinationPath,
        string $outputFormat,
        string $scope = self::SCOPE_PROPERTIES,
    ): void {
        $directory = dirname($destinationPath);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Image output directory cannot be created: ' . $directory);
        }

        if ($outputFormat === 'webp') {
            imagewebp($image, $destinationPath, $this->getWebpQualityForScope($scope));
        } else {
            imageinterlace($image, true);
            imagejpeg($image, $destinationPath, $this->getJpegQualityForScope($scope));
        }

        imagedestroy($image);
    }

    private function createThumbnail(
        string $originalPath,
        string $originalFormat,
        int $thumbMaxDimension = self::THUMB_MAX_DIMENSION,
        ?string $thumbDirectory = null,
    ): void {
        $source = $this->loadImageResource($originalPath, $this->guessMimeTypeFromPath($originalPath));

        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($source);

            return;
        }

        $ratio = min($thumbMaxDimension / $width, $thumbMaxDimension / $height, 1);
        $thumbWidth = max(1, (int) round($width * $ratio));
        $thumbHeight = max(1, (int) round($height * $ratio));

        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        $thumbPath = $this->buildThumbnailStoragePath($originalPath, $thumbDirectory);
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
            return '/uploads/' . self::THUMB_SUBDIRECTORY . '/' . $fileName;
        }

        return '/uploads/' . implode('/', $segments) . '/' . self::THUMB_SUBDIRECTORY . '/' . $fileName;
    }

    private function buildThumbnailStoragePath(string $originalPath, ?string $thumbDirectory = null): ?string
    {
        $baseName = basename($originalPath);
        if ($baseName === '') {
            return null;
        }

        $directory = $thumbDirectory ?? dirname($originalPath);

        return rtrim($directory, '/') . '/' . $baseName;
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
            self::SCOPE_AVATARS => self::SCOPE_AVATARS,
            self::SCOPE_PROPERTIES => self::SCOPE_PROPERTIES,
            self::SCOPE_STATIC_PAGES => self::SCOPE_STATIC_PAGES,
            self::SCOPE_LANDMARKS => self::SCOPE_LANDMARKS,
            default => throw new \InvalidArgumentException('Недопустимая область загрузки'),
        };
    }

    private function getMaxDimensionForScope(string $scope): int
    {
        if ($scope === self::SCOPE_AVATARS) {
            return self::AVATAR_MAX_DIMENSION;
        }

        if ($this->isContentScope($scope)) {
            return self::CONTENT_MAX_DIMENSION;
        }

        return self::MAX_DIMENSION;
    }

    private function getWebpQualityForScope(string $scope): int
    {
        return $this->isContentScope($scope) ? self::CONTENT_WEBP_QUALITY : self::WEBP_QUALITY;
    }

    private function getJpegQualityForScope(string $scope): int
    {
        return $this->isContentScope($scope) ? self::CONTENT_JPEG_QUALITY : self::JPEG_QUALITY;
    }

    private function isContentScope(string $scope): bool
    {
        return $scope === self::SCOPE_ARTICLES
            || $scope === self::SCOPE_STATIC_PAGES
            || $scope === self::SCOPE_LANDMARKS;
    }

    private function getThumbMaxDimensionForScope(string $scope): int
    {
        return $scope === self::SCOPE_AVATARS ? self::AVATAR_THUMB_MAX_DIMENSION : self::THUMB_MAX_DIMENSION;
    }
}
