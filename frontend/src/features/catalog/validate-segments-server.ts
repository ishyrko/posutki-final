import { cache } from "react";
import { fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import {
  isPropertyId,
  parseSegments,
  validatePublicSegmentsStructure,
  CITY_PREFIX_SLUGS,
  resolveCatalogCitySlug,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import type { CityDistrict } from "@/features/city-districts/types";

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

const getCityDistrictsByCitySlug = cache(async (citySlug: string): Promise<CityDistrict[]> => {
  try {
    return await fetchPublicApi<CityDistrict[]>(
      `/cities/${encodeURIComponent(citySlug)}/districts`,
      {
        next: { revalidate: 3600, tags: [`city-districts-${citySlug}`] },
      },
    );
  } catch {
    return [];
  }
});

async function validateParsedCatalogLocation(parsed: ParsedSegments): Promise<boolean> {
  if (parsed.citySlug && !CITY_PREFIX_SLUGS.has(parsed.citySlug)) {
    const city = await getCityBySlug(parsed.citySlug);
    if (!city) return false;
  }

  if (parsed.metroStationSlug) {
    const stationName = await getMetroStationName(parsed.metroStationSlug);
    if (!stationName) return false;
  }

  if (parsed.cityDistrictSlug) {
    const citySlug = resolveCatalogCitySlug(parsed);
    const districts = await getCityDistrictsByCitySlug(citySlug);
    if (!districts.some((district) => district.slug === parsed.cityDistrictSlug)) {
      return false;
    }
  }

  return true;
}

/** Полная проверка сегментов для SSR: структура + город/метро/район в API. */
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

export async function resolveCityDistrictName(
  parsed: ParsedSegments,
): Promise<string | undefined> {
  if (!parsed.cityDistrictSlug) return undefined;

  const citySlug = resolveCatalogCitySlug(parsed);
  const districts = await getCityDistrictsByCitySlug(citySlug);
  return districts.find((district) => district.slug === parsed.cityDistrictSlug)?.name;
}
