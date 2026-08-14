'use client';

import { useCallback, useEffect } from 'react';
import { motion } from 'framer-motion';
import useEmblaCarousel from 'embla-carousel-react';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import {
  getLightboxSlideSrc,
  type GalleryImageSource,
} from './property-gallery-frames';

interface PropertyLightboxProps {
  images: GalleryImageSource[];
  currentIndex: number;
  onIndexChange: (index: number) => void;
  onClose: () => void;
}

export function PropertyLightbox({
  images,
  currentIndex,
  onIndexChange,
  onClose,
}: PropertyLightboxProps) {
  const [emblaRef, emblaApi] = useEmblaCarousel({
    loop: images.length > 1,
    align: 'center',
    duration: 25,
    startIndex: currentIndex,
  });

  const onSelect = useCallback(() => {
    if (!emblaApi) return;
    onIndexChange(emblaApi.selectedScrollSnap());
  }, [emblaApi, onIndexChange]);

  useEffect(() => {
    if (!emblaApi) return;
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

  const scrollPrev = useCallback(() => emblaApi?.scrollPrev(), [emblaApi]);
  const scrollNext = useCallback(() => emblaApi?.scrollNext(), [emblaApi]);

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        onClose();
        return;
      }
      if (images.length <= 1) return;
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        scrollPrev();
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        scrollNext();
      }
    };

    document.addEventListener('keydown', onKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [images.length, onClose, scrollNext, scrollPrev]);

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 z-[100] bg-foreground/95 flex items-center justify-center"
      onClick={onClose}
    >
      <button
        type="button"
        aria-label="Закрыть просмотр"
        onClick={onClose}
        className="cursor-pointer absolute top-4 right-4 z-10 p-2 text-background/70 transition-[opacity,transform,color] duration-150 hover:text-background active:scale-95 active:text-primary active:opacity-100"
      >
        <X className="w-6 h-6" />
      </button>
      {images.length > 1 && (
        <>
          <button
            type="button"
            aria-label="Предыдущее фото"
            onClick={(e) => {
              e.stopPropagation();
              scrollPrev();
            }}
            className="cursor-pointer absolute left-4 z-10 touch-manipulation rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
          >
            <ChevronLeft className="w-6 h-6" />
          </button>
          <button
            type="button"
            aria-label="Следующее фото"
            onClick={(e) => {
              e.stopPropagation();
              scrollNext();
            }}
            className="cursor-pointer absolute right-4 z-10 touch-manipulation rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
          >
            <ChevronRight className="w-6 h-6" />
          </button>
        </>
      )}
      <div
        ref={emblaRef}
        className="w-full max-w-[90vw] touch-pan-y"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex">
          {images.map((image, i) => {
            const src = getLightboxSlideSrc(image, i, currentIndex, images.length);
            return (
              <div
                key={i}
                className="flex min-w-0 flex-[0_0_100%] items-center justify-center"
              >
                {src ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={src}
                    alt=""
                    decoding="async"
                    className="max-h-[85vh] max-w-[90vw] object-contain rounded-lg"
                  />
                ) : (
                  <div className="h-[50vh] w-[60vw] max-w-[90vw] rounded-lg bg-background/10" aria-hidden />
                )}
              </div>
            );
          })}
        </div>
      </div>
      <div className="absolute bottom-6 text-background/70 text-sm pointer-events-none">
        {currentIndex + 1} / {images.length}
      </div>
    </motion.div>
  );
}
