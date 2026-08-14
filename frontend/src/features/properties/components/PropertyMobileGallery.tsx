'use client';

import { useCallback, useEffect, useRef } from 'react';
import useEmblaCarousel from 'embla-carousel-react';
import {
  GalleryPortraitFrame,
  galleryThumbSrc,
  type GalleryImageSource,
} from './property-gallery-frames';

interface PropertyMobileGalleryProps {
  images: GalleryImageSource[];
  currentIndex: number;
  onIndexChange: (index: number) => void;
  onOpenLightbox: () => void;
}

const DRAG_CLICK_THRESHOLD = 8;

export function PropertyMobileGallery({
  images,
  currentIndex,
  onIndexChange,
  onOpenLightbox,
}: PropertyMobileGalleryProps) {
  const pointerStartX = useRef<number | null>(null);
  const dragged = useRef(false);

  const [emblaRef, emblaApi] = useEmblaCarousel({
    loop: images.length > 1,
    align: 'start',
    duration: 25,
  });

  const onSelect = useCallback(() => {
    if (!emblaApi) return;
    onIndexChange(emblaApi.selectedScrollSnap());
  }, [emblaApi, onIndexChange]);

  useEffect(() => {
    if (!emblaApi) return;
    onSelect();
    emblaApi.on('select', onSelect);
    emblaApi.on('reInit', onSelect);
    return () => {
      emblaApi.off('select', onSelect);
      emblaApi.off('reInit', onSelect);
    };
  }, [emblaApi, onSelect]);

  useEffect(() => {
    if (!emblaApi) return;
    if (emblaApi.selectedScrollSnap() !== currentIndex) {
      emblaApi.scrollTo(currentIndex);
    }
  }, [currentIndex, emblaApi]);

  const handlePointerDown = (event: React.PointerEvent<HTMLDivElement>) => {
    pointerStartX.current = event.clientX;
    dragged.current = false;
  };

  const handlePointerMove = (event: React.PointerEvent<HTMLDivElement>) => {
    if (pointerStartX.current == null) return;
    if (Math.abs(event.clientX - pointerStartX.current) > DRAG_CLICK_THRESHOLD) {
      dragged.current = true;
    }
  };

  const handleOpenLightbox = (event: React.MouseEvent) => {
    event.stopPropagation();
    if (!dragged.current) {
      onOpenLightbox();
    }
    pointerStartX.current = null;
    dragged.current = false;
  };

  if (images.length <= 1) {
    const src = galleryThumbSrc(images[0]);
    return (
      <div
        className="absolute inset-0 overflow-hidden cursor-pointer"
        onClick={handleOpenLightbox}
      >
        <GalleryPortraitFrame
          src={src}
          alt="Главное фото"
          fetchPriority="high"
          loading="eager"
        />
      </div>
    );
  }

  return (
    <div
      ref={emblaRef}
      className="absolute inset-0 overflow-hidden cursor-pointer touch-pan-y"
      onPointerDown={handlePointerDown}
      onPointerMove={handlePointerMove}
      onClick={handleOpenLightbox}
    >
      <div className="flex h-full">
        {images.map((image, i) => (
          <div key={i} className="relative min-w-0 flex-[0_0_100%] h-full">
            <GalleryPortraitFrame
              src={galleryThumbSrc(image)}
              alt={`Фото ${i + 1}`}
              loading={i === currentIndex ? 'eager' : 'lazy'}
              fetchPriority={i === currentIndex ? 'high' : undefined}
            />
          </div>
        ))}
      </div>
    </div>
  );
}
