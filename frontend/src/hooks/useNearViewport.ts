import { useEffect, useRef, useState } from "react";

/** Returns true once the element is near or inside the viewport. */
export function useNearViewport<T extends HTMLElement = HTMLDivElement>(rootMargin = "300px") {
  const ref = useRef<T>(null);
  const [isNear, setIsNear] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el || isNear) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsNear(true);
          observer.disconnect();
        }
      },
      { rootMargin, threshold: 0 },
    );

    observer.observe(el);
    return () => observer.disconnect();
  }, [isNear, rootMargin]);

  return { ref, isNear };
}
