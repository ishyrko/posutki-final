"use client";

import { useMemo } from "react";
import { usePathname } from "next/navigation";
import { parseSegments } from "@/features/catalog/slugs";

/** Тип жилья в URL каталога: квартиры по умолчанию, дома — если в пути `/doma/`. */
export function useHeaderCatalogPropertyType(): "apartment" | "house" {
  const pathname = usePathname();

  return useMemo(() => {
    const segments = pathname.split("/").filter(Boolean);
    const parsed = parseSegments(segments);
    return parsed.propertyType === "house" ? "house" : "apartment";
  }, [pathname]);
}
