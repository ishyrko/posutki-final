import { cache } from "react";
import { fetchPublicApi, fetchPublicApiNullable } from "@/lib/server-api";
import { ensureApartmentCatalogSlugsConfigured } from "@/lib/apartment-catalog-slugs-server";
import { isCityPrefixSlug } from "@/features/catalog/apartment-catalog-slug-store";
import {
  isPropertyId,
  parseSegments,
  validatePublicSegmentsStructure,
  resolveCatalogCitySlug,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import type { CityDistrict } from "@/features/city-districts/types";
import type { CityPlace } from "@/features/city-places/types";
import type { Landmark, LandmarkListItem } from "@/features/landmarks/types";

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

const getCityPlacesByCitySlug = cache(async (citySlug: string): Promise<CityPlace[]> => {
  try {
    return await fetchPublicApi<CityPlace[]>(
      `/cities/${encodeURIComponent(citySlug)}/places`,
      {
        next: { revalidate: 3600, tags: [`city-places-${citySlug}`] },
      },
    );
  } catch {
    return [];
  }
});

const getCityLandmarks = cache(async (citySlug: string): Promise<LandmarkListItem[]> => {
  try {
    return await fetchPublicApi<LandmarkListItem[]>(
      `/cities/${encodeURIComponent(citySlug)}/landmarks`,
      {
        next: { revalidate: 3600, tags: [`city-landmarks-${citySlug}`] },
      },
    );
  } catch {
    return [];
  }
});

const getLandmarkBySlug = cache(async (citySlug: string, slug: string): Promise<Landmark | null> => {
  return fetchPublicApiNullable<Landmark>(
    `/cities/${encodeURIComponent(citySlug)}/landmarks/${encodeURIComponent(slug)}`,
    {
      next: { revalidate: 3600, tags: [`landmark-${citySlug}-${slug}`] },
    },
  );
});

async function validateParsedCatalogLocation(parsed: ParsedSegments): Promise<boolean> {
  if (parsed.citySlug && !isCityPrefixSlug(parsed.citySlug)) {
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

  if (parsed.microdistrictSlug || parsed.residentialComplexSlug) {
    const citySlug = resolveCatalogCitySlug(parsed);
    const places = await getCityPlacesByCitySlug(citySlug);
    if (parsed.microdistrictSlug) {
      if (!places.some((place) => place.type === 'microdistrict' && place.slug === parsed.microdistrictSlug)) {
        return false;
      }
    }
    if (parsed.residentialComplexSlug) {
      if (
        !places.some(
          (place) => place.type === 'residential_complex' && place.slug === parsed.residentialComplexSlug,
        )
      ) {
        return false;
      }
    }
  }

  if (parsed.landmarkSlug) {
    const citySlug = resolveCatalogCitySlug(parsed);
    const landmarks = await getCityLandmarks(citySlug);
    if (!landmarks.some((landmark) => landmark.slug === parsed.landmarkSlug)) {
      return false;
    }
  }

  return true;
}

/** Полная проверка сегментов для SSR: структура + город/метро/район в API. */
export async function validatePublicSegments(segments: string[] = []): Promise<boolean> {
  await ensureApartmentCatalogSlugsConfigured();

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

export async function resolveMicrodistrictPlace(
  parsed: ParsedSegments,
): Promise<CityPlace | undefined> {
  if (!parsed.microdistrictSlug) return undefined;

  const citySlug = resolveCatalogCitySlug(parsed);
  const places = await getCityPlacesByCitySlug(citySlug);
  return places.find(
    (place) => place.type === 'microdistrict' && place.slug === parsed.microdistrictSlug,
  );
}

export async function resolveResidentialComplexPlace(
  parsed: ParsedSegments,
): Promise<CityPlace | undefined> {
  if (!parsed.residentialComplexSlug) return undefined;

  const citySlug = resolveCatalogCitySlug(parsed);
  const places = await getCityPlacesByCitySlug(citySlug);
  return places.find(
    (place) => place.type === 'residential_complex' && place.slug === parsed.residentialComplexSlug,
  );
}

export async function resolveLandmark(
  parsed: ParsedSegments,
): Promise<Landmark | null> {
  if (!parsed.landmarkSlug) return null;

  const citySlug = resolveCatalogCitySlug(parsed);
  return getLandmarkBySlug(citySlug, parsed.landmarkSlug);
}

export function resolveLandmarkPhrase(landmark: Landmark | null): string | undefined {
  if (!landmark) return undefined;
  return landmark.nameGenitive;
}
