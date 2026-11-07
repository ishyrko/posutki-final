"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { usePathname } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { Check, ChevronDown, MapPin, Search, X } from "lucide-react";
import Link from "next/link";
import { ALL_HOUSES_CATALOG_PATH, catalogHousePathForRegionSlug } from "@/features/catalog/slugs";
import { fetchApartmentCatalogSnapshot } from "@/features/home/apartment-catalog-cities";
import { fetchHomeCityApartmentCounts } from "@/features/home/city-apartment-counts";
import { buildCityHref } from "@/features/home/city-href";
import { splitHomeApartmentCatalogCities } from "@/features/home/group-home-catalog-cities";
import {
  apartmentCitySlugFromPathname,
  isAllBelarusHousesPath,
  MAIN_APARTMENT_CITY_LABELS,
} from "@/features/home/header-location";
import {
  ALL_BELARUS_HOUSES_LABEL,
  HOUSE_REGIONS,
} from "@/features/home/house-regions";
import { fetchHomeRegionHouseCounts } from "@/features/home/region-house-counts";
import { useHeaderCatalogPropertyType } from "@/hooks/useHeaderCatalogPropertyType";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";
import { cn } from "@/lib/utils";

interface CitySelectorProps {
  onNavigate?: () => void;
  variant?: "toolbar" | "menu";
}

interface LocationRow {
  key: string;
  label: string;
  href: string;
  count?: number;
  active: boolean;
}

const CitySelector = ({ onNavigate, variant = "toolbar" }: CitySelectorProps) => {
  const pathname = usePathname();
  const propertyType = useHeaderCatalogPropertyType();
  const regionSlug = useHeaderRegionSlug();
  const isHouse = propertyType === "house";
  const allBelarus = isAllBelarusHousesPath(pathname);

  const apartmentSlug = apartmentCitySlugFromPathname(pathname) ?? regionSlug;
  const knownApartmentLabel = MAIN_APARTMENT_CITY_LABELS[apartmentSlug];

  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const inputRef = useRef<HTMLInputElement>(null);

  const catalogQuery = useQuery({
    queryKey: ["apartment-catalog-snapshot"],
    queryFn: fetchApartmentCatalogSnapshot,
    enabled: !isHouse && (open || !knownApartmentLabel),
    staleTime: 5 * 60 * 1000,
  });

  const apartmentCountsQuery = useQuery({
    queryKey: ["home-city-apartment-counts"],
    queryFn: fetchHomeCityApartmentCounts,
    enabled: !isHouse && open,
    staleTime: 5 * 60 * 1000,
  });

  const houseCountsQuery = useQuery({
    queryKey: ["home-region-house-counts"],
    queryFn: fetchHomeRegionHouseCounts,
    enabled: isHouse && open,
    staleTime: 5 * 60 * 1000,
  });

  const cities = catalogQuery.data?.cities;
  const prefixSlugs = useMemo(
    () => new Set(catalogQuery.data?.prefixSlugs ?? []),
    [catalogQuery.data?.prefixSlugs],
  );
  const apartmentCounts = apartmentCountsQuery.data ?? {};
  const houseCounts = houseCountsQuery.data ?? {};

  const { mainCities, regionGroups } = useMemo(
    () => splitHomeApartmentCatalogCities(cities ?? []),
    [cities],
  );
  const allCities = useMemo(
    () => [...mainCities, ...regionGroups.flatMap((group) => group.cities)],
    [mainCities, regionGroups],
  );

  const apartmentLabel =
    allCities.find((city) => city.slug === apartmentSlug)?.name ?? knownApartmentLabel;

  const houseRegion = HOUSE_REGIONS.find((region) => region.slug === regionSlug);
  const buttonLabel = isHouse
    ? allBelarus
      ? ALL_BELARUS_HOUSES_LABEL
      : (houseRegion?.name ?? HOUSE_REGIONS[0].name)
    : apartmentLabel;

  const normalizedQuery = query.trim().toLowerCase();
  const searchResults = normalizedQuery
    ? allCities.filter((city) => city.name.toLowerCase().includes(normalizedQuery))
    : null;

  const houseCountsReady = HOUSE_REGIONS.every((region) => houseCounts[region.slug] != null);
  const houseTotal = HOUSE_REGIONS.reduce((sum, region) => sum + (houseCounts[region.slug] ?? 0), 0);

  useEffect(() => {
    if (!open || isHouse) return;
    const timeoutId = window.setTimeout(() => inputRef.current?.focus(), 50);
    return () => window.clearTimeout(timeoutId);
  }, [open, isHouse]);

  useEffect(() => {
    if (!open) return;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.addEventListener("keydown", onKey);
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = previousOverflow;
    };
  }, [open]);

  const close = () => {
    setOpen(false);
    onNavigate?.();
  };

  const row = ({ key, label, href, count, active }: LocationRow) => (
    <Link
      key={key}
      href={href}
      onClick={close}
      className="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg text-left hover:bg-muted/60 transition-colors duration-150 group"
    >
      <span className="flex items-center gap-2 min-w-0">
        <MapPin className="h-3.5 w-3.5 text-primary/60 shrink-0" />
        <span
          className={cn(
            "text-sm truncate",
            active ? "font-semibold text-primary" : "text-foreground group-hover:text-primary",
          )}
        >
          {label}
        </span>
      </span>
      <span className="flex items-center gap-2 shrink-0">
        {count != null ? <span className="text-xs text-muted-foreground">{count}</span> : null}
        {active ? <Check className="h-4 w-4 text-primary" /> : null}
      </span>
    </Link>
  );

  const dialog = (
    <div
      className="fixed inset-0 z-[60] flex items-start justify-center bg-foreground/40 backdrop-blur-sm p-4 pt-24 md:pt-32 animate-fade-in"
      onClick={() => setOpen(false)}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-label={isHouse ? "Выбор области" : "Выбор города"}
        className="w-full max-w-lg rounded-2xl bg-card border border-border shadow-elevated overflow-hidden"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-center justify-between px-5 pt-5 pb-4">
          <h2 className="font-display text-lg font-bold text-foreground">
            {isHouse ? "Выберите область" : "Выберите город"}
          </h2>
          <button
            type="button"
            onClick={() => setOpen(false)}
            className="p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/60 transition-colors"
            aria-label="Закрыть"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {isHouse ? null : (
          <div className="px-5 pb-4">
            <div className="relative">
              <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <input
                ref={inputRef}
                type="text"
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Начните вводить название города…"
                className="w-full h-11 pl-10 pr-4 rounded-xl border border-border bg-background text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/50 transition-shadow"
              />
            </div>
          </div>
        )}

        <div className="max-h-[50vh] overflow-y-auto px-3 pb-4">
          {isHouse ? (
            <div className="space-y-0.5">
              {row({
                key: "all",
                label: ALL_BELARUS_HOUSES_LABEL,
                href: ALL_HOUSES_CATALOG_PATH,
                count: houseCountsReady ? houseTotal : undefined,
                active: allBelarus,
              })}
              {HOUSE_REGIONS.map((region) =>
                row({
                  key: region.slug,
                  label: region.name,
                  href: catalogHousePathForRegionSlug(region.slug),
                  count: houseCounts[region.slug],
                  active: !allBelarus && region.slug === regionSlug,
                }),
              )}
            </div>
          ) : catalogQuery.isPending && !cities?.length ? (
            <p className="px-3 py-8 text-sm text-muted-foreground text-center">Загрузка…</p>
          ) : !cities?.length ? (
            <p className="px-3 py-8 text-sm text-muted-foreground text-center">
              Не удалось загрузить города
            </p>
          ) : searchResults ? (
            searchResults.length > 0 ? (
              <div className="space-y-0.5">
                {searchResults.map((city) =>
                  row({
                    key: city.slug,
                    label: city.name,
                    href: buildCityHref(city, prefixSlugs),
                    count: apartmentCounts[city.slug],
                    active: city.slug === apartmentSlug,
                  }),
                )}
              </div>
            ) : (
              <p className="px-3 py-8 text-sm text-muted-foreground text-center">
                Город «{query.trim()}» не найден. Попробуйте другое название.
              </p>
            )
          ) : (
            <>
              <p className="px-3 pt-1 pb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Популярные города
              </p>
              <div className="grid grid-cols-2 gap-0.5 mb-4">
                {mainCities.map((city) =>
                  row({
                    key: city.slug,
                    label: city.name,
                    href: buildCityHref(city, prefixSlugs),
                    count: apartmentCounts[city.slug],
                    active: city.slug === apartmentSlug,
                  }),
                )}
              </div>
              {regionGroups.map((group) => (
                <div key={group.slug} className="mb-3">
                  <p className="px-3 pt-2 pb-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {group.label}
                  </p>
                  <div className="grid grid-cols-2 gap-0.5">
                    {group.cities.map((city) =>
                      row({
                        key: city.slug,
                        label: city.name,
                        href: buildCityHref(city, prefixSlugs),
                        count: apartmentCounts[city.slug],
                        active: city.slug === apartmentSlug,
                      }),
                    )}
                  </div>
                </div>
              ))}
            </>
          )}
        </div>
      </div>
    </div>
  );

  return (
    <>
      <button
        type="button"
        onClick={() => {
          setQuery("");
          setOpen(true);
        }}
        className={cn(
          "flex cursor-pointer items-center gap-1.5 text-sm font-medium text-foreground hover:text-primary transition-colors duration-150",
          variant === "toolbar" && "max-w-[10.5rem] xl:max-w-[14rem]",
        )}
        aria-haspopup="dialog"
        aria-expanded={open}
        aria-label={buttonLabel ?? (isHouse ? "Выбор области" : "Выбор города")}
        title={buttonLabel}
      >
        <MapPin className="h-4 w-4 text-primary shrink-0" />
        {buttonLabel ? (
          <span
            className={cn(
              "border-b border-dashed border-muted-foreground/60 pb-px",
              variant === "toolbar" && "truncate",
            )}
          >
            {buttonLabel}
          </span>
        ) : (
          <span className="inline-block h-4 w-16 rounded bg-muted animate-pulse" aria-hidden />
        )}
        <ChevronDown className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
      </button>
      {open ? createPortal(dialog, document.body) : null}
    </>
  );
};

export default CitySelector;
