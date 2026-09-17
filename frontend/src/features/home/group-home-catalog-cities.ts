import { HEADER_CITY_SLUGS } from "@/lib/region-header";
import type { ApartmentCatalogCity } from "@/features/home/apartment-catalog-cities";

const REGION_OBLAST_LABELS: Record<string, string> = {
  minsk: "Минская область",
  brest: "Брестская область",
  vitebsk: "Витебская область",
  gomel: "Гомельская область",
  grodno: "Гродненская область",
  mogilev: "Могилёвская область",
};

const OTHER_REGION_SLUG = "other";
const OTHER_REGION_LABEL = "Другие города";

export interface HomeCatalogRegionGroup {
  slug: string;
  label: string;
  cities: ApartmentCatalogCity[];
}

const compareCityNamesRu = (a: ApartmentCatalogCity, b: ApartmentCatalogCity): number =>
  a.name.localeCompare(b.name, "ru", { sensitivity: "base" });

function regionGroupLabel(slug: string, cities: ApartmentCatalogCity[]): string {
  if (slug === OTHER_REGION_SLUG) {
    return OTHER_REGION_LABEL;
  }

  if (REGION_OBLAST_LABELS[slug]) {
    return REGION_OBLAST_LABELS[slug];
  }

  const regionName = cities[0]?.regionName?.trim();
  if (regionName) {
    return regionName.includes("область") ? regionName : `${regionName} область`;
  }

  return OTHER_REGION_LABEL;
}

/** Областные центры отдельно, остальные города — по областям в порядке шапки. */
export function splitHomeApartmentCatalogCities(cities: ApartmentCatalogCity[]): {
  mainCities: ApartmentCatalogCity[];
  regionGroups: HomeCatalogRegionGroup[];
} {
  const mainCities = cities.filter((city) => city.isMain);
  const secondaryCities = cities.filter((city) => !city.isMain);
  const byRegion = new Map<string, ApartmentCatalogCity[]>();

  for (const city of secondaryCities) {
    const slug = city.regionSlug || OTHER_REGION_SLUG;
    const list = byRegion.get(slug) ?? [];
    list.push(city);
    byRegion.set(slug, list);
  }

  const groups: HomeCatalogRegionGroup[] = [];

  for (const slug of HEADER_CITY_SLUGS) {
    const regionCities = byRegion.get(slug);
    if (!regionCities?.length) {
      continue;
    }

    groups.push({
      slug,
      label: regionGroupLabel(slug, regionCities),
      cities: [...regionCities].sort(compareCityNamesRu),
    });
    byRegion.delete(slug);
  }

  const remainingSlugs = [...byRegion.keys()].sort((a, b) => {
    if (a === OTHER_REGION_SLUG) {
      return 1;
    }
    if (b === OTHER_REGION_SLUG) {
      return -1;
    }

    return regionGroupLabel(a, byRegion.get(a) ?? []).localeCompare(
      regionGroupLabel(b, byRegion.get(b) ?? []),
      "ru",
      { sensitivity: "base" },
    );
  });

  for (const slug of remainingSlugs) {
    const regionCities = byRegion.get(slug);
    if (!regionCities?.length) {
      continue;
    }

    groups.push({
      slug,
      label: regionGroupLabel(slug, regionCities),
      cities: [...regionCities].sort(compareCityNamesRu),
    });
  }

  return { mainCities, regionGroups: groups };
}
