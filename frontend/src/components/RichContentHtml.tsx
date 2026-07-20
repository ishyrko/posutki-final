"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { ChevronLeft, ChevronRight, X } from "lucide-react";
import { cn } from "@/lib/utils";

type RichContentHtmlProps = {
  html: string;
  className?: string;
};

export function RichContentHtml({ html, className }: RichContentHtmlProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [images, setImages] = useState<string[]>([]);

  const openLightbox = useCallback((index: number, imageUrls: string[]) => {
    setImages(imageUrls);
    setCurrentIndex(index);
    setLightboxOpen(true);
  }, []);

  const closeLightbox = useCallback(() => {
    setLightboxOpen(false);
  }, []);

  const showPrevious = useCallback(() => {
    setCurrentIndex((index) => (index > 0 ? index - 1 : images.length - 1));
  }, [images.length]);

  const showNext = useCallback(() => {
    setCurrentIndex((index) => (index < images.length - 1 ? index + 1 : 0));
  }, [images.length]);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) {
      return undefined;
    }

    const collectImageUrls = (): string[] =>
      Array.from(container.querySelectorAll("img"))
        .map((img) => img.getAttribute("src")?.trim() || "")
        .filter((src) => src !== "");

    const openFromImage = (img: HTMLImageElement) => {
      const src = img.getAttribute("src")?.trim();
      if (!src) {
        return;
      }

      const imageUrls = collectImageUrls();
      const index = imageUrls.indexOf(src);
      if (index === -1) {
        return;
      }

      openLightbox(index, imageUrls);
    };

    const onClick = (event: MouseEvent) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement) || !container.contains(target)) {
        return;
      }

      openFromImage(target);
    };

    const onKeyDown = (event: KeyboardEvent) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement) || !container.contains(target)) {
        return;
      }

      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      event.preventDefault();
      openFromImage(target);
    };

    container.addEventListener("click", onClick);
    container.addEventListener("keydown", onKeyDown);

    return () => {
      container.removeEventListener("click", onClick);
      container.removeEventListener("keydown", onKeyDown);
    };
  }, [html, openLightbox]);

  useEffect(() => {
    if (!lightboxOpen) {
      return undefined;
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        closeLightbox();
      }
      if (event.key === "ArrowLeft") {
        showPrevious();
      }
      if (event.key === "ArrowRight") {
        showNext();
      }
    };

    document.addEventListener("keydown", onKeyDown);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [lightboxOpen, closeLightbox, showNext, showPrevious]);

  const currentImage = images[currentIndex] ?? "";
  const hasMultipleImages = images.length > 1;

  return (
    <>
      <div
        ref={containerRef}
        className={cn(
          "[&_img]:!my-6 [&_img]:!block [&_img]:!h-auto [&_img]:!w-full [&_img]:cursor-zoom-in [&_img]:rounded-lg [&_img]:transition-opacity [&_img]:hover:opacity-95",
          className,
        )}
        dangerouslySetInnerHTML={{ __html: html }}
      />

      <AnimatePresence>
        {lightboxOpen && currentImage && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-[100] flex items-center justify-center bg-foreground/95"
            onClick={closeLightbox}
          >
            <button
              type="button"
              aria-label="Закрыть просмотр"
              onClick={closeLightbox}
              className="absolute top-4 right-4 cursor-pointer p-2 text-background/70 transition-[opacity,transform,color] duration-150 hover:text-background active:scale-95 active:text-primary active:opacity-100"
            >
              <X className="h-6 w-6" />
            </button>

            {hasMultipleImages && (
              <button
                type="button"
                aria-label="Предыдущее изображение"
                onClick={(event) => {
                  event.stopPropagation();
                  showPrevious();
                }}
                className="absolute left-4 touch-manipulation cursor-pointer rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
              >
                <ChevronLeft className="h-6 w-6" />
              </button>
            )}

            {/* eslint-disable-next-line @next/next/no-img-element */}
            <motion.img
              key={currentImage}
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              src={currentImage}
              alt=""
              className="max-h-[85vh] max-w-[90vw] rounded-lg object-contain"
              onClick={(event) => event.stopPropagation()}
            />

            {hasMultipleImages && (
              <button
                type="button"
                aria-label="Следующее изображение"
                onClick={(event) => {
                  event.stopPropagation();
                  showNext();
                }}
                className="absolute right-4 touch-manipulation cursor-pointer rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
              >
                <ChevronRight className="h-6 w-6" />
              </button>
            )}

            {hasMultipleImages && (
              <div className="absolute bottom-6 text-sm text-background/70">
                {currentIndex + 1} / {images.length}
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}
