"use client";

import { useEffect, useMemo, useState } from "react";
import {
  Building2,
  TreePine,
  Waves,
  Castle,
  Factory,
  Landmark,
  MapPin,
  Home,
  Mountain,
} from "lucide-react";
import Link from "next/link";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import {
  fetchHomeCityApartmentCounts,
  formatApartmentCount,
} from "@/features/home/city-apartment-counts";

const cities = [
  /** Минск — без префикса региона в URL, как в каталоге. */
  {
    name: "Минск",
    slug: "minsk",
    icon: Building2,
    href: buildCatalogUrl({ propertyType: "apartment" }),
  },
  {
    name: "Брест",
    slug: "brest",
    icon: Landmark,
    href: buildCatalogUrl({ region: "brest", propertyType: "apartment" }),
  },
  {
    name: "Витебск",
    slug: "vitebsk",
    icon: Waves,
    href: buildCatalogUrl({ region: "vitebsk", propertyType: "apartment" }),
  },
  {
    name: "Гродно",
    slug: "grodno",
    icon: Castle,
    href: buildCatalogUrl({ region: "grodno", propertyType: "apartment" }),
  },
  {
    name: "Гомель",
    slug: "gomel",
    icon: Factory,
    href: buildCatalogUrl({ region: "gomel", propertyType: "apartment" }),
  },
  {
    name: "Могилёв",
    slug: "mogilev",
    icon: TreePine,
    href: buildCatalogUrl({ region: "mogilev", propertyType: "apartment" }),
  },
  {
    name: "Барановичи",
    slug: "baranovichi",
    icon: MapPin,
    href: buildCatalogUrl({ city: "baranovichi", propertyType: "apartment" }),
  },
  {
    name: "Пинск",
    slug: "pinsk",
    icon: Home,
    href: buildCatalogUrl({ city: "pinsk", propertyType: "apartment" }),
  },
  {
    name: "Бобруйск",
    slug: "bobruysk",
    icon: Factory,
    href: buildCatalogUrl({ city: "bobruysk", propertyType: "apartment" }),
  },
  {
    name: "Молодечно",
    slug: "molodechno",
    icon: Building2,
    href: buildCatalogUrl({ city: "molodechno", propertyType: "apartment" }),
  },
  {
    name: "Орша",
    slug: "orsha",
    icon: MapPin,
    href: buildCatalogUrl({ city: "orsha", propertyType: "apartment" }),
  },
  {
    name: "Новополоцк",
    slug: "novopolotsk",
    icon: Factory,
    href: buildCatalogUrl({ city: "novopolotsk", propertyType: "apartment" }),
  },
  {
    name: "Светлогорск",
    slug: "svetlogorsk",
    icon: Mountain,
    href: buildCatalogUrl({ city: "svetlogorsk", propertyType: "apartment" }),
  },
  {
    name: "Сморгонь",
    slug: "smorgon",
    icon: Castle,
    href: buildCatalogUrl({ city: "smorgon", propertyType: "apartment" }),
  },
];

interface CitySectionProps {
  apartmentCountsBySlug?: Record<string, number>;
}

const CitySection = ({ apartmentCountsBySlug }: CitySectionProps) => {
  const [clientCounts, setClientCounts] = useState<Record<string, number> | null>(null);

  const counts = useMemo(
    () => ({ ...apartmentCountsBySlug, ...clientCounts }),
    [apartmentCountsBySlug, clientCounts],
  );

  const needsClientFetch = useMemo(
    () => cities.some((city) => counts[city.slug] == null),
    [counts],
  );

  useEffect(() => {
    if (!needsClientFetch) return;

    let cancelled = false;

    fetchHomeCityApartmentCounts().then((fetched) => {
      if (!cancelled && Object.keys(fetched).length > 0) {
        setClientCounts(fetched);
      }
    });

    return () => {
      cancelled = true;
    };
  }, [needsClientFetch]);

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
            const Icon = city.icon;
            return (
              <Link
                key={city.slug}
                href={city.href}
                className="group flex flex-col items-center gap-3 p-6 rounded-xl bg-card shadow-card hover:shadow-card-hover transition-all duration-200 hover:-translate-y-1"
              >
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors duration-150">
                  <Icon className="h-6 w-6 text-primary" />
                </div>
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
