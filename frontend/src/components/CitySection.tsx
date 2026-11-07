"use client";

import { useEffect, useMemo, useState } from "react";
import {
  Castle,
  ChevronDown,
  ChevronsDown,
  ChevronsUp,
  Factory,
  Landmark,
  MapPin,
  Train,
  TreePine,
  Waves,
  type LucideIcon,
} from "lucide-react";
import Link from "next/link";
import { buildCityHref } from "@/features/home/city-href";
import {
  fetchApartmentCatalogCities,
  type ApartmentCatalogCity,
} from "@/features/home/apartment-catalog-cities";
import {
  fetchHomeCityApartmentCounts,
  formatApartmentCount,
} from "@/features/home/city-apartment-counts";
import { splitHomeApartmentCatalogCities } from "@/features/home/group-home-catalog-cities";
import { HOUSE_REGION_ICON_BY_SLUG } from "@/features/home/house-regions";

/** Icons for the "other cities" region cards, matching the design section. */
const OTHER_REGION_ICONS: Record<string, LucideIcon> = {
  minsk: Train,
  brest: Landmark,
  grodno: Castle,
  vitebsk: Waves,
  gomel: Factory,
  mogilev: TreePine,
};

function pluralRu(count: number, one: string, few: string, many: string): string {
  const mod10 = count % 10;
  const mod100 = count % 100;
  if (mod10 === 1 && mod100 !== 11) {
    return `${count} ${one}`;
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) {
    return `${count} ${few}`;
  }
  return `${count} ${many}`;
}

interface CitySectionProps {
  apartmentCatalogCities?: ApartmentCatalogCity[];
  apartmentCountsBySlug?: Record<string, number>;
}

const CityCard = ({
  city,
  count,
}: {
  city: ApartmentCatalogCity;
  count?: number;
}) => {
  const Icon = city.isMain ? HOUSE_REGION_ICON_BY_SLUG[city.slug] : undefined;
  const featured = Boolean(city.isMain && Icon);

  return (
    <Link
      href={buildCityHref(city)}
      className={
        featured
          ? "group flex flex-col items-center gap-3 p-6 rounded-xl bg-card shadow-card hover:shadow-card-hover transition-all duration-200 hover:-translate-y-1"
          : "group flex flex-col items-center justify-center gap-0.5 px-4 py-3 rounded-xl bg-card shadow-card hover:shadow-card-hover transition-all duration-200 hover:-translate-y-1"
      }
    >
      {Icon && featured ? (
        <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors duration-150">
          <Icon className="h-6 w-6 text-primary" />
        </div>
      ) : null}
      <div className="text-center">
        <p className="font-display font-semibold text-foreground">{city.name}</p>
        {count != null ? (
          <p className="text-sm text-muted-foreground">{formatApartmentCount(count)}</p>
        ) : null}
      </div>
    </Link>
  );
};

const CitySection = ({ apartmentCatalogCities, apartmentCountsBySlug }: CitySectionProps) => {
  const [clientCities, setClientCities] = useState<ApartmentCatalogCity[] | null>(null);
  const [clientCounts, setClientCounts] = useState<Record<string, number> | null>(null);
  const [openRegions, setOpenRegions] = useState<string[]>([]);

  const cities = useMemo(
    () => clientCities ?? apartmentCatalogCities ?? [],
    [clientCities, apartmentCatalogCities],
  );
  const counts = useMemo(
    () => ({ ...apartmentCountsBySlug, ...clientCounts }),
    [apartmentCountsBySlug, clientCounts],
  );

  const { mainCities, regionGroups } = useMemo(
    () => splitHomeApartmentCatalogCities(cities),
    [cities],
  );

  const secondaryCitiesNeedRegion =
    cities.some((city) => !city.isMain) &&
    cities.filter((city) => !city.isMain).every((city) => !city.regionSlug);
  const needsClientCities = cities.length === 0 || secondaryCitiesNeedRegion;
  const needsClientCounts = useMemo(
    () => cities.some((city) => counts[city.slug] == null),
    [cities, counts],
  );

  const allRegionsOpen =
    regionGroups.length > 0 && openRegions.length === regionGroups.length;

  useEffect(() => {
    if (!needsClientCities) return;

    let cancelled = false;

    fetchApartmentCatalogCities().then((fetched) => {
      if (!cancelled && fetched.length > 0) {
        setClientCities(fetched);
      }
    });

    return () => {
      cancelled = true;
    };
  }, [needsClientCities]);

  useEffect(() => {
    if (!needsClientCounts) return;

    let cancelled = false;

    fetchHomeCityApartmentCounts().then((fetched) => {
      if (!cancelled && Object.keys(fetched).length > 0) {
        setClientCounts(fetched);
      }
    });

    return () => {
      cancelled = true;
    };
  }, [needsClientCounts]);

  if (cities.length === 0) {
    return null;
  }

  return (
    <section className="bg-surface pt-12 pb-6 md:pt-14 md:pb-5 lg:pt-16 lg:pb-6">
      <div className="container mx-auto px-4">
        <div className="mb-6 md:mb-8">
          <h2 className="text-3xl font-bold text-foreground font-display mb-2">
            Квартиры на сутки в Беларуси
          </h2>
          <p className="text-muted-foreground">
            Посуточная аренда по всей Беларуси - более 700 активных объявлений.
          </p>
        </div>

        {mainCities.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {mainCities.map((city) => (
              <CityCard key={city.slug} city={city} count={counts[city.slug]} />
            ))}
          </div>
        ) : null}
      </div>

      {regionGroups.length > 0 && !secondaryCitiesNeedRegion ? (
        <section className="bg-background py-16 md:py-24">
          <div className="container mx-auto px-4">
            <div className="flex flex-wrap items-end justify-between gap-4 mb-3">
              <div>
                <h2 className="font-display text-2xl md:text-3xl font-bold text-foreground">
                  Другие города
                </h2>
                <p className="text-muted-foreground mt-2">
                  Посуточная аренда жилья в районах Беларуси
                </p>
              </div>

              <button
                type="button"
                onClick={() =>
                  setOpenRegions(allRegionsOpen ? [] : regionGroups.map((group) => group.slug))
                }
                aria-expanded={allRegionsOpen}
                className="flex cursor-pointer items-center gap-2 px-4 py-2.5 rounded-xl border border-border bg-card text-sm font-medium text-muted-foreground hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-colors duration-150"
              >
                {allRegionsOpen ? (
                  <ChevronsUp className="h-4 w-4" />
                ) : (
                  <ChevronsDown className="h-4 w-4" />
                )}
                {allRegionsOpen ? "Свернуть все" : "Развернуть все"}
              </button>
            </div>

            <div className="mt-8 space-y-3">
              {regionGroups.map((group) => {
                const Icon = OTHER_REGION_ICONS[group.slug] ?? MapPin;
                const isOpen = openRegions.includes(group.slug);
                const countsReady = group.cities.every((city) => counts[city.slug] != null);
                const apartmentCount = group.cities.reduce(
                  (sum, city) => sum + (counts[city.slug] ?? 0),
                  0,
                );

                return (
                  <div
                    key={group.slug}
                    className="rounded-xl bg-card border border-border shadow-card hover:shadow-card-hover transition-shadow duration-200 overflow-hidden"
                  >
                    <button
                      type="button"
                      onClick={() =>
                        setOpenRegions((current) =>
                          current.includes(group.slug)
                            ? current.filter((slug) => slug !== group.slug)
                            : [...current, group.slug],
                        )
                      }
                      aria-expanded={isOpen}
                      className="w-full flex cursor-pointer items-center gap-3 p-4 md:p-5 text-left group"
                    >
                      <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 group-hover:bg-primary/15 transition-colors duration-150">
                        <Icon className="h-5 w-5 text-primary" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <span className="font-display font-semibold text-foreground block">
                          {group.label}
                        </span>
                        <span className="text-sm text-muted-foreground">
                          {pluralRu(group.cities.length, "город", "города", "городов")}
                          {countsReady
                            ? ` · ${formatApartmentCount(apartmentCount)}`
                            : ""}
                        </span>
                      </div>
                      <ChevronDown
                        className={`h-5 w-5 text-muted-foreground shrink-0 transition-transform duration-200 ${
                          isOpen ? "rotate-180" : ""
                        }`}
                      />
                    </button>

                    {/* Kept in the HTML while collapsed so city links stay in the first response. */}
                    <div hidden={!isOpen} className="border-t border-border px-4 md:px-5 py-4">
                      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-1">
                        {group.cities.map((city) => (
                          <Link
                            key={city.slug}
                            href={buildCityHref(city)}
                            className="flex items-center justify-between gap-2 py-2 text-sm border-b border-transparent hover:border-primary/20 transition-colors duration-150 group"
                          >
                            <span className="flex items-center gap-2 min-w-0">
                              <MapPin className="h-3.5 w-3.5 text-primary/60 shrink-0" />
                              <span className="text-foreground truncate group-hover:text-primary transition-colors duration-150">
                                {city.name}
                              </span>
                            </span>
                            {counts[city.slug] != null ? (
                              <span className="text-xs text-muted-foreground shrink-0">
                                {counts[city.slug]}
                              </span>
                            ) : null}
                          </Link>
                        ))}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>
      ) : null}
    </section>
  );
};

export default CitySection;
