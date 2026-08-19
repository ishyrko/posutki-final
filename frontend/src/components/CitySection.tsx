"use client";

import { useEffect, useMemo, useState } from "react";
import {
  Building2,
  TreePine,
  Waves,
  Castle,
  Factory,
  Landmark,
  type LucideIcon,
} from "lucide-react";
import Link from "next/link";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import {
  fetchApartmentCatalogCities,
  type ApartmentCatalogCity,
} from "@/features/home/apartment-catalog-cities";
import {
  fetchHomeCityApartmentCounts,
  formatApartmentCount,
} from "@/features/home/city-apartment-counts";

const FEATURED_CITY_ICONS: Record<string, LucideIcon> = {
  minsk: Building2,
  brest: Landmark,
  vitebsk: Waves,
  grodno: Castle,
  gomel: Factory,
  mogilev: TreePine,
};

const buildCityHref = (city: ApartmentCatalogCity): string => {
  if (city.slug === "minsk") {
    return buildCatalogUrl({ propertyType: "apartment" });
  }

  if (city.isMain) {
    return buildCatalogUrl({ region: city.slug, propertyType: "apartment" });
  }

  return buildCatalogUrl({ city: city.slug, propertyType: "apartment" });
};

interface CitySectionProps {
  apartmentCatalogCities?: ApartmentCatalogCity[];
  apartmentCountsBySlug?: Record<string, number>;
}

const CitySection = ({ apartmentCatalogCities, apartmentCountsBySlug }: CitySectionProps) => {
  const [clientCities, setClientCities] = useState<ApartmentCatalogCity[] | null>(null);
  const [clientCounts, setClientCounts] = useState<Record<string, number> | null>(null);

  const cities = clientCities ?? apartmentCatalogCities ?? [];
  const counts = useMemo(
    () => ({ ...apartmentCountsBySlug, ...clientCounts }),
    [apartmentCountsBySlug, clientCounts],
  );

  const needsClientCities = cities.length === 0;
  const needsClientCounts = useMemo(
    () => cities.some((city) => counts[city.slug] == null),
    [cities, counts],
  );

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
          <p className="text-muted-foreground">Посуточная аренда по всей Беларуси</p>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {cities.map((city) => {
            const Icon = city.isMain ? FEATURED_CITY_ICONS[city.slug] : undefined;
            const featured = Boolean(city.isMain && Icon);

            return (
              <Link
                key={city.slug}
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
                  {counts[city.slug] != null ? (
                    <p className="text-sm text-muted-foreground">
                      {formatApartmentCount(counts[city.slug])}
                    </p>
                  ) : null}
                </div>
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default CitySection;
