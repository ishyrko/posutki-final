import { cache } from "react";
import { fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import {
  isPropertyId,
  parseSegments,
  validatePublicSegmentsStructure,
  type ParsedSegments,
} from "@/features/catalog/slugs";

interface CityResponse {
  slug: string;
}

interface MetroStationResponse {
  slug: string;
  name: string;
}

const getCityBySlug = cache(async (slug: string): Promise<CityResponse | null> => {
  return fetchPublicApiNullable<CityResponse>(`/cities/${encodeURIComponent(slug)}`, {
    next: { revalidate: 3600, tags: [`city-${slug}`] },
  });
});

const getMetroStationName = cache(async (slug: string): Promise<string | undefined> => {
  try {
    const stations = await fetchPublicApi<MetroStationResponse[]>("/metro/stations?cityId=1", {
      next: { revalidate: 3600, tags: ["metro-stations"] },
    });
    return stations.find((station) => station.slug === slug)?.name;
  } catch {
    return undefined;
  }
});

async function validateParsedCatalogLocation(parsed: ParsedSegments): Promise<boolean> {
  if (parsed.citySlug) {
    const city = await getCityBySlug(parsed.citySlug);
    if (!city) return false;
  }

  if (parsed.metroStationSlug) {
    const stationName = await getMetroStationName(parsed.metroStationSlug);
    if (!stationName) return false;
  }

  return true;
}

/** Полная проверка сегментов для SSR: структура + город/метро в API. */
export async function validatePublicSegments(segments: string[] = []): Promise<boolean> {
  if (!validatePublicSegmentsStructure(segments)) {
    return false;
  }

  if (segments.length === 0) {
    return true;
  }

  const catalogSegments = isPropertyId(segments[segments.length - 1]!)
    ? segments.slice(0, -1)
    : segments;

  return validateParsedCatalogLocation(parseSegments(catalogSegments));
}

export async function resolveMetroStationName(slug?: string): Promise<string | undefined> {
  if (!slug) return undefined;
  return getMetroStationName(slug);
}
