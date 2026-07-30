'use client';

import { useEffect, useState } from 'react';

/** Side-column letterbox with blurred edges. Parent must be `relative` with explicit size. */
export function GalleryPortraitFrame({
  src,
  alt,
  className = 'absolute inset-0 flex min-h-0 min-w-0',
}: {
  src: string;
  alt: string;
  className?: string;
}) {
  return (
    <div className={`${className} overflow-hidden`}>
      <div className="relative min-h-0 min-w-0 flex-1 overflow-hidden">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={src}
          alt=""
          aria-hidden
          className="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover object-left blur-md"
        />
      </div>
      <div className="relative z-[1] flex h-full min-w-0 max-w-full shrink items-center justify-center overflow-hidden">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={src} alt={alt} className="max-h-full w-auto max-w-full object-contain" />
      </div>
      <div className="relative min-h-0 min-w-0 flex-1 overflow-hidden">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={src}
          alt=""
          aria-hidden
          className="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover object-right blur-md"
        />
      </div>
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
}: {
  src: string;
  alt: string;
  preferCover?: boolean;
}) {
  const clearlyLandscape = useImageClearlyLandscape(src);

  return (
    <div className="relative aspect-[4/3] w-full overflow-hidden">
      {preferCover || clearlyLandscape === true ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={src} alt={alt} className="absolute inset-0 h-full w-full object-cover" />
      ) : (
        <GalleryPortraitFrame src={src} alt={alt} />
      )}
    </div>
  );
}
