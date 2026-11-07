"use client";

import { useMemo } from "react";
import { usePathname } from "next/navigation";

/**
 * Catalog property type for the header: houses when the path contains `usadby`
 * or starts with `usadby-v-belarusi`, apartments otherwise.
 * Does not use parseSegments or the apartment-catalog slug store — Header also
 * renders on pages that do not configure that store.
 */
export function useHeaderCatalogPropertyType(): "apartment" | "house" {
  const pathname = usePathname();

  return useMemo(() => {
    const segments = pathname.split("/").filter(Boolean);
    if (segments[0] === "usadby-v-belarusi" || segments.includes("usadby")) {
      return "house";
    }
    return "apartment";
  }, [pathname]);
}
