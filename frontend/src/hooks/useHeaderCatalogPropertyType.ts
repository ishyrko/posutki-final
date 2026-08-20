"use client";

import { useMemo } from "react";
import { usePathname } from "next/navigation";

/**
 * Тип жилья в URL каталога: квартиры по умолчанию, дома — если в пути есть сегмент `doma`.
 * Не использует parseSegments / apartment-catalog slug store — Header рендерится и в кабинете
 * без ApartmentCatalogSlugProvider.
 */
export function useHeaderCatalogPropertyType(): "apartment" | "house" {
  const pathname = usePathname();

  return useMemo(() => {
    const segments = pathname.split("/").filter(Boolean);
    return segments.includes("doma") ? "house" : "apartment";
  }, [pathname]);
}
