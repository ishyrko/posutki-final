"use client";

import { useEffect, useMemo, useState, useSyncExternalStore } from "react";
import { usePathname } from "next/navigation";
import {
  HEADER_CITY_SLUG_SET,
  HEADER_REGION_MINSK_SLUG,
  LISTING_REGION_SESSION_KEY,
  REGION_SYNC_EVENT,
} from "@/lib/region-header";
import { isPropertyDetailPath } from "@/features/catalog/slugs";

/**
 * Текущий slug региона для шапки/футера: из URL, с главной — Минск, на карточке — sessionStorage при необходимости.
 */
export function useHeaderRegionSlug(): string {
  const pathname = usePathname();
  const [regionVersion, setRegionVersion] = useState(0);

  useEffect(() => {
    const bump = () => setRegionVersion((v) => v + 1);
    window.addEventListener(REGION_SYNC_EVENT, bump);
    return () => window.removeEventListener(REGION_SYNC_EVENT, bump);
  }, []);

  const isMounted = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );

  return useMemo(() => {
    if (!isMounted) return HEADER_REGION_MINSK_SLUG;

    const firstSegment = pathname.split("/").filter(Boolean)[0] ?? "";
    const fromPathSlug = HEADER_CITY_SLUG_SET.has(firstSegment)
      ? firstSegment
      : HEADER_REGION_MINSK_SLUG;

    if (pathname === "/") return HEADER_REGION_MINSK_SLUG;

    if (isPropertyDetailPath(pathname)) {
      try {
        const listing = sessionStorage.getItem(LISTING_REGION_SESSION_KEY);
        if (listing && HEADER_CITY_SLUG_SET.has(listing)) {
          return listing;
        }
      } catch {
        /* sessionStorage unavailable */
      }
    }

    if (fromPathSlug !== HEADER_REGION_MINSK_SLUG) return fromPathSlug;
    return HEADER_REGION_MINSK_SLUG;
  }, [isMounted, pathname, regionVersion]);
}
