<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

final class PropertyImageWatermark
{
    private const WIDTH_RATIO = 0.22;
    private const MARGIN_RATIO = 0.03;

    public function __construct(
        private readonly string $watermarkImagePath,
    ) {
    }

    public function applyToFile(string $sourcePath, string $destinationPath, string $outputFormat): bool
    {
        if (!is_file($this->watermarkImagePath)) {
            throw new \RuntimeException('Watermark image is missing: ' . $this->watermarkImagePath);
        }

        if (!is_file($sourcePath)) {
            throw new \RuntimeException('Source image is missing: ' . $sourcePath);
        }

        $mimeType = mime_content_type($sourcePath) ?: '';
        $image = $this->loadImageResource($sourcePath, $mimeType);
        if ($image === false) {
            throw new \RuntimeException(sprintf(
                'Cannot decode source image %s (mime=%s)',
                $sourcePath,
                $mimeType !== '' ? $mimeType : 'unknown',
            ));
        }

        $watermarked = $this->applyToImage($image);
        if ($watermarked === false) {
            imagedestroy($image);

            throw new \RuntimeException('Failed to composite watermark onto ' . $sourcePath);
        }

        if ($watermarked !== $image) {
            imagedestroy($image);
        }

        $this->writeImage($watermarked, $destinationPath, $outputFormat);
        imagedestroy($watermarked);

        return true;
    }

    /**
     * @return \GdImage|false
     */
    public function applyToImage(\GdImage $image): \GdImage|false
    {
        if (!is_file($this->watermarkImagePath)) {
            return false;
        }

        $watermark = @imagecreatefrompng($this->watermarkImagePath);
        if ($watermark === false) {
            return false;
        }

        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        if ($imageWidth <= 0 || $imageHeight <= 0 || $watermarkWidth <= 0 || $watermarkHeight <= 0) {
            imagedestroy($watermark);

            return false;
        }

        $targetWatermarkWidth = max(1, (int) round($imageWidth * self::WIDTH_RATIO));
        $targetWatermarkHeight = max(1, (int) round($watermarkHeight * ($targetWatermarkWidth / $watermarkWidth)));
        $margin = max(8, (int) round(min($imageWidth, $imageHeight) * self::MARGIN_RATIO));

        $resizedWatermark = imagecreatetruecolor($targetWatermarkWidth, $targetWatermarkHeight);
        imagealphablending($resizedWatermark, false);
        imagesavealpha($resizedWatermark, true);
        $transparent = imagecolorallocatealpha($resizedWatermark, 0, 0, 0, 127);
        imagefill($resizedWatermark, 0, 0, $transparent);
        imagealphablending($resizedWatermark, false);
        imagesavealpha($resizedWatermark, true);
        imagecopyresampled(
            $resizedWatermark,
            $watermark,
            0,
            0,
            0,
            0,
            $targetWatermarkWidth,
            $targetWatermarkHeight,
            $watermarkWidth,
            $watermarkHeight,
        );
        imagedestroy($watermark);

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $destX = max(0, $imageWidth - $targetWatermarkWidth - $margin);
        $destY = max(0, $imageHeight - $targetWatermarkHeight - $margin);
        $this->compositeWithAlpha($image, $resizedWatermark, $destX, $destY);
        imagedestroy($resizedWatermark);

        return $image;
    }

    private function compositeWithAlpha(\GdImage $destination, \GdImage $overlay, int $destX, int $destY): void
    {
        $overlayWidth = imagesx($overlay);
        $overlayHeight = imagesy($overlay);
        $destWidth = imagesx($destination);
        $destHeight = imagesy($destination);

        for ($y = 0; $y < $overlayHeight; ++$y) {
            $targetY = $destY + $y;
            if ($targetY < 0 || $targetY >= $destHeight) {
                continue;
            }

            for ($x = 0; $x < $overlayWidth; ++$x) {
                $targetX = $destX + $x;
                if ($targetX < 0 || $targetX >= $destWidth) {
                    continue;
                }

                $overlayColor = imagecolorat($overlay, $x, $y);
                $alpha = ($overlayColor >> 24) & 0x7F;
                if ($alpha >= 127) {
                    continue;
                }

                $overlayOpacity = 1 - ($alpha / 127);
                $overlayRed = ($overlayColor >> 16) & 0xFF;
                $overlayGreen = ($overlayColor >> 8) & 0xFF;
                $overlayBlue = $overlayColor & 0xFF;

                $destColor = imagecolorat($destination, $targetX, $targetY);
                $destRed = ($destColor >> 16) & 0xFF;
                $destGreen = ($destColor >> 8) & 0xFF;
                $destBlue = $destColor & 0xFF;

                $red = (int) round($overlayRed * $overlayOpacity + $destRed * (1 - $overlayOpacity));
                $green = (int) round($overlayGreen * $overlayOpacity + $destGreen * (1 - $overlayOpacity));
                $blue = (int) round($overlayBlue * $overlayOpacity + $destBlue * (1 - $overlayOpacity));

                $color = imagecolorallocate($destination, $red, $green, $blue);
                imagesetpixel($destination, $targetX, $targetY, $color);
            }
        }
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

    private function writeImage(\GdImage $image, string $destinationPath, string $outputFormat): void
    {
        if ($outputFormat === 'webp' && function_exists('imagewebp')) {
            imagewebp($image, $destinationPath, 82);
        } else {
            imageinterlace($image, true);
            imagejpeg($image, $destinationPath, 84);
        }
    }
}
