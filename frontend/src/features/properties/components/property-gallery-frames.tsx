'use client';

import { useEffect, useState } from 'react';

export type GalleryImageSource = {
  url: string;
  thumbnailUrl?: string | null;
};

export function galleryThumbSrc(image: GalleryImageSource): string {
  return image.thumbnailUrl || image.url;
}

export function galleryFullSrc(image: GalleryImageSource): string {
  return image.url;
}

type GalleryImgProps = {
  loading?: 'lazy' | 'eager';
  fetchPriority?: 'high' | 'low' | 'auto';
  decoding?: 'async' | 'sync' | 'auto';
};

/**
 * Letterbox any aspect ratio into the parent: full image via object-contain,
 * blurred cover fill behind (sides for portrait, top/bottom for landscape).
 * Parent must be `relative` with explicit size.
 */
export function GalleryPortraitFrame({
  src,
  blurSrc,
  alt,
  className = 'absolute inset-0',
  loading,
  fetchPriority,
  decoding = 'async',
}: {
  src: string;
  blurSrc?: string;
  alt: string;
  className?: string;
} & GalleryImgProps) {
  const blurStyle = { backgroundImage: `url("${blurSrc ?? src}")` };

  return (
    <div className={`${className} overflow-hidden`}>
      <div
        aria-hidden
        className="pointer-events-none absolute inset-0 scale-110 bg-cover bg-center blur-md"
        style={blurStyle}
      />
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img
        src={src}
        alt={alt}
        loading={loading}
        fetchPriority={fetchPriority}
        decoding={decoding}
        className="relative z-[1] h-full w-full object-contain"
      />
    </div>
  );
}

/** True only when image is clearly wider than tall. */
export function useImageClearlyLandscape(src: string): boolean | null {
  const [clearlyLandscape, setClearlyLandscape] = useState<boolean | null>(null);

  useEffect(() => {
    setClearlyLandscape(null);
    const img = new Image();
    const finish = () => {
      if (img.naturalWidth > 0 && img.naturalHeight > 0) {
        setClearlyLandscape(img.naturalWidth > img.naturalHeight * 1.02);
      }
    };
    img.addEventListener('load', finish);
    img.addEventListener('error', () => setClearlyLandscape(false));
    img.src = src;
    if (img.complete) finish();
    return () => {
      img.removeEventListener('load', finish);
    };
  }, [src]);

  return clearlyLandscape;
}

export function GalleryGridThumb({
  src,
  alt,
  preferCover = false,
  loading = 'lazy',
  decoding = 'async',
}: {
  src: string;
  alt: string;
  preferCover?: boolean;
} & GalleryImgProps) {
  const clearlyLandscape = useImageClearlyLandscape(src);

  return (
    <div className="relative aspect-[4/3] w-full overflow-hidden">
      {preferCover || clearlyLandscape === true ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={src}
          alt={alt}
          loading={loading}
          decoding={decoding}
          className="absolute inset-0 h-full w-full object-cover"
        />
      ) : (
        <GalleryPortraitFrame src={src} alt={alt} loading={loading} decoding={decoding} />
      )}
    </div>
  );
}

function lightboxNeighborIndexes(currentIndex: number, total: number): Set<number> {
  if (total <= 1) {
    return new Set([0]);
  }
  const prev = (currentIndex - 1 + total) % total;
  const next = (currentIndex + 1) % total;
  return new Set([currentIndex, prev, next]);
}

export function getLightboxSlideSrc(
  image: GalleryImageSource,
  index: number,
  currentIndex: number,
  total: number,
): string | undefined {
  if (lightboxNeighborIndexes(currentIndex, total).has(index)) {
    return galleryFullSrc(image);
  }
  return image.thumbnailUrl ?? undefined;
}
