"use client";

import { useEffect, useMemo, useState } from "react";
import { Building2, TreePine, Waves, Castle, Factory, Landmark } from "lucide-react";
import Link from "next/link";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import {
  fetchHomeRegionHouseCounts,
  formatHouseCount,
} from "@/features/home/region-house-counts";

const regions = [
  {
    name: "Минская",
    slug: "minsk",
    icon: Building2,
    href: buildCatalogUrl({ propertyType: "house" }),
  },
  {
    name: "Брестская",
    slug: "brest",
    icon: Landmark,
    href: buildCatalogUrl({ region: "brest", propertyType: "house" }),
  },
  {
    name: "Витебская",
    slug: "vitebsk",
    icon: Waves,
    href: buildCatalogUrl({ region: "vitebsk", propertyType: "house" }),
  },
  {
    name: "Гродненская",
    slug: "grodno",
    icon: Castle,
    href: buildCatalogUrl({ region: "grodno", propertyType: "house" }),
  },
  {
    name: "Гомельская",
    slug: "gomel",
    icon: Factory,
    href: buildCatalogUrl({ region: "gomel", propertyType: "house" }),
  },
  {
    name: "Могилёвская",
    slug: "mogilev",
    icon: TreePine,
    href: buildCatalogUrl({ region: "mogilev", propertyType: "house" }),
  },
];

interface RegionHouseSectionProps {
  houseCountsBySlug?: Record<string, number>;
}

const RegionHouseSection = ({ houseCountsBySlug }: RegionHouseSectionProps) => {
  const [clientCounts, setClientCounts] = useState<Record<string, number> | null>(null);

  const counts = useMemo(
    () => ({ ...houseCountsBySlug, ...clientCounts }),
    [houseCountsBySlug, clientCounts],
  );

  const needsClientFetch = useMemo(
    () => regions.some((region) => counts[region.slug] == null),
    [counts],
  );

  useEffect(() => {
    if (!needsClientFetch) return;

    let cancelled = false;

    fetchHomeRegionHouseCounts().then((fetched) => {
      if (!cancelled && Object.keys(fetched).length > 0) {
        setClientCounts(fetched);
      }
    });

    return () => {
      cancelled = true;
    };
  }, [needsClientFetch]);

  return (
    <section className="bg-surface pt-6 pb-10 md:pt-8 md:pb-6 lg:pt-10 lg:pb-8">
      <div className="container mx-auto px-4">
        <div className="mb-6 md:mb-8">
          <h2 className="text-3xl font-bold text-foreground font-display mb-2">
            Дома и коттеджи на сутки в Беларуси
          </h2>
          <p className="text-muted-foreground">Посуточная аренда домов по областям</p>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {regions.map((region) => {
            const Icon = region.icon;
            return (
              <Link
                key={region.slug}
                href={region.href}
                className="group flex flex-col items-center gap-3 p-6 rounded-xl bg-card shadow-card hover:shadow-card-hover transition-all duration-200 hover:-translate-y-1"
              >
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors duration-150">
                  <Icon className="h-6 w-6 text-primary" />
                </div>
                <div className="text-center">
                  <p className="font-display font-semibold text-foreground leading-tight">
                    {region.name}
                    <br />
                    область
                  </p>
                  {counts[region.slug] != null ? (
                    <p className="text-sm text-muted-foreground">
                      {formatHouseCount(counts[region.slug])}
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

export default RegionHouseSection;
