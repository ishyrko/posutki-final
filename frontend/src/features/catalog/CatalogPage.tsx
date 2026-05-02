'use client';

import { useEffect, useMemo, useState } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { motion } from "framer-motion";
import { Search, SlidersHorizontal, X, Map as MapIcon, LayoutGrid, LayoutList, MapPin, ChevronLeft, ChevronRight, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import PropertyListCard from "@/components/PropertyListCard";
import PropertyMap, { type MapProperty } from "@/components/PropertyMap";
import { useProperties, useExchangeRates } from "@/features/properties/hooks";
import { useMetroStations } from "@/features/metro/hooks";
import type { NearbyMetroStation } from "@/features/metro/types";
import { Property, formatAddress, type PriceType, type Currency } from "@/features/properties/types";
import type { ExchangeRates } from "@/features/properties/api";
import {
  convertPrice,
  formatPrice,
  formatPropertyPrices,
  DEFAULT_EXCHANGE_RATES_FALLBACK,
} from "@/features/properties/price-display";
import { buildPageTitle, type ParsedSegments } from "@/features/catalog/slugs";
import { showBathrooms, showRooms, showRoomsCatalogFilter } from "@/features/create-listing/property-field-rules";
import { cn } from "@/lib/utils";

type ViewMode = "list" | "list-map" | "map";

const currencies: { value: Currency; label: string }[] = [
  { value: "BYN", label: "BYN" },
  { value: "USD", label: "USD" },
  { value: "EUR", label: "EUR" },
];

const roomOptions = [
  { value: "all", label: "Все" },
  { value: "1", label: "1" },
  { value: "2", label: "2" },
  { value: "3", label: "3" },
  { value: "4", label: "4+" },
];

const sortOptions = [
  { value: "default", label: "По умолчанию" },
  { value: "price-asc", label: "Цена: по возрастанию" },
  { value: "price-desc", label: "Цена: по убыванию" },
  { value: "area-desc", label: "Площадь: больше" },
];

const viewModes: { value: ViewMode; icon: typeof LayoutList; label: string }[] = [
  { value: "list", icon: LayoutList, label: "Список" },
  { value: "list-map", icon: LayoutGrid, label: "Список + Карта" },
  { value: "map", icon: MapIcon, label: "Карта" },
];

/** One station for the card: filtered station when metro filter is active, otherwise the closest. */
function pickMetroStationsForCatalog(
  stations: NearbyMetroStation[],
  filterStationId: number | null,
): NearbyMetroStation[] {
  if (!stations.length) return [];
  if (filterStationId != null) {
    const match = stations.find((s) => s.id === filterStationId);
    return match ? [match] : [];
  }
  const sorted = [...stations].sort((a, b) => a.distanceKm - b.distanceKm);
  return sorted.slice(0, 1);
}

function propertyToListCard(p: Property, rates: ExchangeRates, metroFilterStationId: number | null) {
  const { primary, secondary } = formatPropertyPrices(p, rates);
  return {
    image: p.images?.[0]?.thumbnailUrl || p.images?.[0]?.url || "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800",
    price: primary,
    secondaryPrice: secondary,
    title: p.title,
    address: formatAddress(p.address),
    beds: showRooms(p.type) ? (p.specifications.rooms ?? null) : null,
    baths: showBathrooms(p.type) ? (p.specifications.bathrooms ?? null) : null,
    area: p.specifications.area || 0,
    landArea: p.specifications.landArea,
    description: p.description,
    floor: p.specifications.floor && p.specifications.totalFloors
      ? `${p.specifications.floor}/${p.specifications.totalFloors}`
      : undefined,
    id: p.id,
    propertyType: p.type,
    nearbyMetroStations: pickMetroStationsForCatalog(p.nearbyMetroStations ?? [], metroFilterStationId),
  };
}

function propertyToMapItem(p: Property, rates: ExchangeRates): MapProperty | null {
  if (!p.coordinates?.latitude || !p.coordinates?.longitude) return null;
  const bynAmount = convertPrice(p.price.amount, p.price.currency, "BYN", rates);
  return {
    id: p.id,
    lat: p.coordinates.latitude,
    lng: p.coordinates.longitude,
    title: p.title,
    price: formatPrice(bynAmount, "BYN"),
    address: formatAddress(p.address),
    image: p.images?.[0]?.thumbnailUrl || p.images?.[0]?.url || "",
    dealType: p.dealType,
    propertyType: p.type,
  };
}

interface CatalogPageProps {
  parsed: ParsedSegments;
  title: string;
}

export default function CatalogPage({ parsed, title }: CatalogPageProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [search, setSearch] = useState("");
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [priceType, setPriceType] = useState<PriceType>("total");
  const [currency, setCurrency] = useState<Currency>("BYN");
  const [rooms, setRooms] = useState("all");
  const [metroStationId, setMetroStationId] = useState("all");
  const [nearMetro, setNearMetro] = useState(false);
  const [sort, setSort] = useState("default");
  const [showFilters, setShowFilters] = useState(false);
  const [viewMode, setViewMode] = useState<ViewMode>("list-map");
  const [activeMarker, setActiveMarker] = useState<number | null>(null);
  const isSaleDeal = false;
  const pageFromQuery = Number(searchParams.get("page") ?? "1");
  const validPageFromQuery = Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const [currentPage, setCurrentPage] = useState(validPageFromQuery);
  const itemsPerPage = 6;
  const { data: metroStations = [] } = useMetroStations(1);
  const roomsFilterVisible = showRoomsCatalogFilter(parsed.propertyType);

  useEffect(() => {
    setCurrentPage(validPageFromQuery);
  }, [validPageFromQuery]);

  const roomsFromQuery = searchParams.get("rooms");
  useEffect(() => {
    if (!roomsFilterVisible) return;
    const r = roomsFromQuery;
    if (r === "1" || r === "2" || r === "3") setRooms(r);
    else if (r === "4" || r === "4+") setRooms("4");
  }, [roomsFromQuery, roomsFilterVisible]);

  useEffect(() => {
    if (!roomsFilterVisible) {
      setRooms("all");
    }
  }, [roomsFilterVisible]);

  /** "List + map" is hidden on narrow viewports; avoid leaving users in an unavailable mode */
  useEffect(() => {
    const mq = window.matchMedia("(max-width: 767px)");
    const collapseListMapOnNarrow = () => {
      if (mq.matches) {
        setViewMode((v) => (v === "list-map" ? "list" : v));
      }
    };
    collapseListMapOnNarrow();
    mq.addEventListener("change", collapseListMapOnNarrow);
    return () => mq.removeEventListener("change", collapseListMapOnNarrow);
  }, []);

  const hasPriceFilter = minPrice !== "" || maxPrice !== "";
  const metroStationsByLine = useMemo(() => {
    const byLine = new Map<number, typeof metroStations>();
    for (const station of metroStations) {
      const list = byLine.get(station.line) ?? [];
      list.push(station);
      byLine.set(station.line, list);
    }

    return [
      { line: 1, label: "Московская", stations: byLine.get(1) ?? [] },
      { line: 2, label: "Автозаводская", stations: byLine.get(2) ?? [] },
      { line: 3, label: "Зеленолужская", stations: byLine.get(3) ?? [] },
    ];
  }, [metroStations]);
  const activeFilterCount = [
    hasPriceFilter,
    roomsFilterVisible && rooms !== "all",
    nearMetro && metroStationId !== "all",
    nearMetro,
  ].filter(Boolean).length;

  const pageTitle = useMemo(() => {
    if (!parsed.metroStationSlug) return title;
    const station = metroStations.find((s) => s.slug === parsed.metroStationSlug);
    return station ? buildPageTitle(parsed, undefined, station.name) : title;
  }, [parsed, metroStations, title]);

  /** Same station id as sent to the API when filtering by metro (URL or sidebar). */
  const metroFilterStationId = useMemo((): number | null => {
    const routeMetroStation = parsed.metroStationSlug
      ? metroStations.find((station) => station.slug === parsed.metroStationSlug)
      : undefined;
    if (routeMetroStation) return routeMetroStation.id;
    if (nearMetro && metroStationId !== "all") return Number(metroStationId);
    return null;
  }, [parsed.metroStationSlug, metroStations, nearMetro, metroStationId]);

  const filters = useMemo(() => {
    const f: Record<string, unknown> = {
      page: currentPage,
      limit: itemsPerPage,
      sortBy: "createdAt",
      sortOrder: "DESC" as const,
    };
    const routeMetroStation = parsed.metroStationSlug
      ? metroStations.find((station) => station.slug === parsed.metroStationSlug)
      : undefined;

    if (parsed.dealType) f.dealType = parsed.dealType;
    /**
     * Explicit city in URL → filter by city. Regional path (/brest/…) → filter by region only.
     * Default catalog (no prefix): Minsk region = region slug `minsk`, not city slug —
     * otherwise only the city of Minsk matches and most seeded listings disappear.
     */
    if (parsed.citySlug) {
      f.citySlug = parsed.citySlug;
    } else if (parsed.regionSlug) {
      f.regionSlug = parsed.regionSlug;
    } else {
      f.regionSlug = "minsk";
    }
    if (parsed.propertyType) f.type = parsed.propertyType;
    if (roomsFilterVisible && rooms !== "all") {
      f.rooms = rooms === "4" ? undefined : Number(rooms);
    }
    if (routeMetroStation) {
      f.metroStationId = routeMetroStation.id;
    } else if (nearMetro && metroStationId !== "all") {
      f.metroStationId = Number(metroStationId);
    }
    if (parsed.nearMetro || nearMetro) {
      f.nearMetro = true;
    }
    if (minPrice) f.minPrice = Number(minPrice);
    if (maxPrice) f.maxPrice = Number(maxPrice);
    if (isSaleDeal && hasPriceFilter && priceType !== "total") f.priceType = priceType;
    if (hasPriceFilter) f.currency = currency;
    if (sort === "price-asc") { f.sortBy = "price"; f.sortOrder = "ASC"; }
    else if (sort === "price-desc") { f.sortBy = "price"; f.sortOrder = "DESC"; }
    else if (sort === "area-desc") { f.sortBy = "area"; f.sortOrder = "DESC"; }
    return f;
  }, [currentPage, parsed.dealType, parsed.regionSlug, parsed.propertyType, parsed.citySlug, parsed.nearMetro, parsed.metroStationSlug, metroStations, roomsFilterVisible, rooms, metroStationId, nearMetro, minPrice, maxPrice, priceType, currency, hasPriceFilter, isSaleDeal, sort]);

  const { data, isLoading } = useProperties(filters);
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const properties = useMemo(() => data?.data ?? [], [data?.data]);
  const totalItems = data?.meta?.total || 0;
  const totalPages = Math.ceil(totalItems / itemsPerPage);

  const changePage = (nextPage: number) => {
    const params = new URLSearchParams(searchParams.toString());
    if (nextPage <= 1) {
      params.delete("page");
    } else {
      params.set("page", String(nextPage));
    }

    const query = params.toString();
    router.push(query ? `${pathname}?${query}` : pathname);
  };

  const resetToFirstPage = () => {
    if (currentPage === 1) return;

    setCurrentPage(1);
    const params = new URLSearchParams(searchParams.toString());
    params.delete("page");
    const query = params.toString();
    router.replace(query ? `${pathname}?${query}` : pathname);
  };

  const displayProperties = useMemo(() => {
    if (!search) return properties;
    return properties.filter(
      (p) =>
        p.title.toLowerCase().includes(search.toLowerCase()) ||
        p.address.streetName?.toLowerCase().includes(search.toLowerCase()) ||
        p.address.cityName?.toLowerCase().includes(search.toLowerCase())
    );
  }, [properties, search]);

  const mapProperties: MapProperty[] = useMemo(() => {
    return displayProperties
      .map((p) => propertyToMapItem(p, exchangeRates))
      .filter((m): m is MapProperty => m !== null);
  }, [displayProperties, exchangeRates]);

  const clearFilters = () => {
    setMinPrice("");
    setMaxPrice("");
    setPriceType("total");
    setCurrency("BYN");
    setRooms("all");
    setMetroStationId("all");
    setNearMetro(false);
    setSearch("");
    resetToFirstPage();
  };

  const showList = viewMode === "list" || viewMode === "list-map";
  const showMap = viewMode === "map" || viewMode === "list-map";

  return (
    <div className="min-h-screen bg-background">
      {/* Hero header */}
      <section className="bg-muted/40 border-b border-border pt-20 pb-8">
        <div className="container mx-auto px-4">
          <h1 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-3">
            {pageTitle}
          </h1>
          <div className="flex items-center justify-between">
            <p className="text-muted-foreground">
              {isLoading ? "Загрузка..." : `Найдено ${totalItems} объектов`}
            </p>

            <div className="flex rounded-lg bg-muted p-0.5 w-fit">
              {viewModes.map((m) => (
                <button
                  key={m.value}
                  type="button"
                  onClick={() => setViewMode(m.value)}
                  className={cn(
                    "flex items-center gap-1.5 px-3 py-2 text-sm font-medium transition-colors",
                    m.value === "list-map" && "hidden md:flex",
                    viewMode === m.value
                      ? "bg-primary text-primary-foreground"
                      : "text-muted-foreground hover:text-foreground",
                  )}
                >
                  <m.icon className="w-4 h-4" />
                  <span className="hidden sm:inline">{m.label}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Filters bar */}
      <section className="sticky top-16 z-30 bg-card border-b border-border shadow-sm">
        <div className="container mx-auto px-4 py-3">
          <div className="flex flex-wrap items-center gap-3">
            <div className="relative min-w-0 flex-1 max-w-md basis-[min(100%,20rem)]">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                placeholder="Поиск по названию или адресу..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-10 h-10"
              />
            </div>

            <Button
              variant="outline"
              onClick={() => setShowFilters(!showFilters)}
              className="relative"
            >
              <SlidersHorizontal className="w-4 h-4 mr-2" />
              Фильтры
              {activeFilterCount > 0 && (
                <span className="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center">
                  {activeFilterCount}
                </span>
              )}
            </Button>

            <div className="hidden md:block ml-auto">
              <Select value={sort} onValueChange={setSort}>
                <SelectTrigger className="w-[200px] h-10">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent className="bg-card border-border z-50">
                  {sortOptions.map((o) => (
                    <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {showFilters && (
            <motion.div
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: "auto", opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
              className="overflow-hidden"
            >
              {/* xl: flex row + content-sized widths; avoid w-full on flex children (stretches to full row) */}
              <div
                className={cn(
                  "grid min-w-0 grid-cols-1 md:grid-cols-2 gap-3 pt-3 mt-3 border-t border-border",
                  "xl:flex xl:flex-row xl:flex-wrap xl:items-start xl:gap-x-5 xl:gap-y-3",
                )}
              >
                <div className="min-w-0 md:col-span-2 xl:w-max xl:max-w-full xl:shrink-0">
                  <label className="text-xs text-muted-foreground mb-1 block">Цена</label>
                  <div className="flex w-full min-w-0 flex-nowrap items-center gap-x-1.5 sm:flex-wrap sm:gap-x-2 sm:gap-y-2">
                    <Input
                      type="number"
                      placeholder="От"
                      value={minPrice}
                      onChange={(e) => { setMinPrice(e.target.value); resetToFirstPage(); }}
                      className="h-10 min-w-0 flex-1 basis-0 px-2 text-base tabular-nums focus-visible:ring-inset focus-visible:ring-offset-0 sm:text-sm sm:w-[112px] sm:flex-none sm:basis-auto sm:px-3"
                    />
                    <span className="-mx-0.5 shrink-0 text-muted-foreground text-xs leading-none sm:mx-0">—</span>
                    <Input
                      type="number"
                      placeholder="До"
                      value={maxPrice}
                      onChange={(e) => { setMaxPrice(e.target.value); resetToFirstPage(); }}
                      className="h-10 min-w-0 flex-1 basis-0 px-2 text-base tabular-nums focus-visible:ring-inset focus-visible:ring-offset-0 sm:text-sm sm:w-[112px] sm:flex-none sm:basis-auto sm:px-3"
                    />
                    {isSaleDeal && (
                      <div className="flex h-10 w-[5rem] max-[399px]:w-[4.5rem] shrink-0 overflow-hidden rounded-md border border-border sm:h-auto sm:w-auto">
                        <button
                          type="button"
                          onClick={() => setPriceType("total")}
                          className={`flex flex-1 items-center justify-center px-1 py-0 text-xs font-medium transition-colors sm:flex-none sm:px-2 sm:py-2 sm:text-sm ${
                            priceType === "total"
                              ? "bg-primary text-primary-foreground"
                              : "bg-background text-muted-foreground hover:bg-muted"
                          }`}
                        >
                          <span className="sm:hidden">все</span>
                          <span className="hidden sm:inline">всего</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => setPriceType("perMeter")}
                          className={`flex flex-1 items-center justify-center border-l border-border px-1 py-0 text-xs font-medium transition-colors sm:flex-none sm:px-2 sm:py-2 sm:text-sm ${
                            priceType === "perMeter"
                              ? "bg-primary text-primary-foreground"
                              : "bg-background text-muted-foreground hover:bg-muted"
                          }`}
                        >
                          <span className="sm:hidden">м²</span>
                          <span className="hidden sm:inline">за м²</span>
                        </button>
                      </div>
                    )}
                    <Select value={currency} onValueChange={(v) => setCurrency(v as Currency)}>
                      <SelectTrigger className="h-10 w-[3.65rem] shrink-0 px-2 text-xs focus:ring-inset focus:ring-offset-0 sm:w-[88px] sm:px-3 sm:text-sm">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-card border-border z-50">
                        {currencies.map((c) => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                {roomsFilterVisible && (
                  <div className="min-w-0 w-full max-w-2xl xl:w-auto xl:max-w-2xl xl:shrink-0">
                    <label className="text-xs text-muted-foreground mb-1 block">Комнат</label>
                    <div className="flex min-w-0 flex-wrap gap-2">
                      {roomOptions.map((r) => (
                        <button
                          key={r.value}
                          type="button"
                          onClick={() => { setRooms(r.value); resetToFirstPage(); }}
                          className={`h-10 min-w-[3.25rem] flex-1 basis-0 rounded-md px-2.5 text-sm font-medium transition-colors sm:min-w-[3.5rem] ${
                            rooms === r.value
                              ? "bg-primary text-primary-foreground"
                              : "bg-muted text-muted-foreground hover:bg-muted/80"
                          }`}
                        >
                          {r.label}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
                <div className="min-w-0 w-[min(100%,18rem)] shrink-0 space-y-3 max-sm:px-0.5 xl:flex-none">
                  <div className="w-full min-w-0">
                    <label className="text-xs text-muted-foreground mb-1 block">Станция метро</label>
                    <Select value={metroStationId} onValueChange={setMetroStationId} disabled={!nearMetro}>
                      <SelectTrigger className="h-10 w-full min-w-0 justify-between gap-2 text-left focus:ring-inset focus:ring-offset-0 [&>span]:min-w-0 [&>span]:truncate">
                        <SelectValue placeholder="Любая станция" />
                      </SelectTrigger>
                      <SelectContent className="bg-card border-border z-50 max-h-72">
                        <SelectItem value="all">Любая станция</SelectItem>
                        {metroStationsByLine.map((group) => (
                          group.stations.length > 0 ? (
                            <SelectGroup key={group.line}>
                              <SelectLabel
                                className={`pl-6 ${
                                  group.line === 1
                                    ? "text-[#006DB7]"
                                    : group.line === 2
                                      ? "text-[#E3000B]"
                                      : "text-[#007A33]"
                                }`}
                              >
                                {group.label}
                              </SelectLabel>
                              {group.stations.map((station) => (
                                <SelectItem key={station.id} value={String(station.id)}>
                                  {station.name}
                                </SelectItem>
                              ))}
                            </SelectGroup>
                          ) : null
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <label className="inline-flex items-center gap-2 text-sm text-foreground/80 cursor-pointer">
                    <Checkbox
                      checked={nearMetro}
                      onCheckedChange={(checked) => {
                        const enabled = checked === true;
                        setNearMetro(enabled);
                        if (!enabled) {
                          setMetroStationId("all");
                        }
                      }}
                    />
                    Рядом с метро
                  </label>
                </div>
                {activeFilterCount > 0 && (
                  <div className="md:col-span-2 flex items-start justify-end self-end xl:ml-auto xl:shrink-0">
                    <Button variant="ghost" size="sm" onClick={clearFilters} className="text-muted-foreground whitespace-nowrap">
                      <X className="w-3.5 h-3.5 mr-1" />
                      Сбросить фильтры
                    </Button>
                  </div>
                )}
              </div>
            </motion.div>
          )}
        </div>
      </section>

      {/* Results */}
      <section className="container mx-auto px-4 py-8">
        <div className={`flex gap-6 ${viewMode === "list-map" ? "lg:flex-row" : ""} flex-col`}>
          {/* Cards */}
          {showList && (
            <div className={viewMode === "list-map" ? "lg:w-1/2 xl:w-3/5" : "w-full"}>
              {isLoading ? (
                <div className="flex flex-col gap-5">
                  {[...Array(3)].map((_, i) => (
                    <div key={i} className="h-48 bg-muted animate-pulse rounded-2xl" />
                  ))}
                </div>
              ) : displayProperties.length > 0 ? (
                <>
                  <div className="flex flex-col gap-5">
                    {displayProperties.map((property, i) => {
                      const card = propertyToListCard(property, exchangeRates, metroFilterStationId);
                      return (
                        <div
                          key={property.id}
                          onMouseEnter={() => setActiveMarker(property.id)}
                          onMouseLeave={() => setActiveMarker(null)}
                        >
                          <PropertyListCard
                            {...card}
                            index={i}
                            metroOnSeparateLine={viewMode === "list-map"}
                          />
                        </div>
                      );
                    })}
                  </div>

                  {/* Pagination */}
                  {totalPages > 1 && (
                    <div className="flex items-center justify-center gap-2 mt-8">
                      <Button
                        variant="outline"
                        size="icon"
                        disabled={currentPage === 1}
                        onClick={() => changePage(Math.max(1, currentPage - 1))}
                        className="h-9 w-9"
                      >
                        <ChevronLeft className="w-4 h-4" />
                      </Button>
                      {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                        <Button
                          key={page}
                          variant={currentPage === page ? "default" : "outline"}
                          size="icon"
                          onClick={() => changePage(page)}
                          className="h-9 w-9"
                        >
                          {page}
                        </Button>
                      ))}
                      <Button
                        variant="outline"
                        size="icon"
                        disabled={currentPage === totalPages}
                        onClick={() => changePage(Math.min(totalPages, currentPage + 1))}
                        className="h-9 w-9"
                      >
                        <ChevronRight className="w-4 h-4" />
                      </Button>
                    </div>
                  )}
                </>
              ) : (
                <div className="text-center py-20">
                  <MapPin className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
                  <h3 className="text-xl font-display font-semibold text-foreground mb-2">Ничего не найдено</h3>
                  <p className="text-muted-foreground mb-2">Попробуйте изменить параметры поиска</p>
                  <p className="text-sm text-muted-foreground mb-6">Или будьте первым, кто разместит объявление в этом разделе.</p>
                  <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Button size="default" asChild className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0">
                      <Link href="/razmestit/">
                        <Plus className="w-4 h-4 mr-1.5" />
                        Подать объявление
                      </Link>
                    </Button>
                    <Button variant="outline" onClick={clearFilters}>Сбросить фильтры</Button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Map */}
          {showMap && (
            <div className={
              viewMode === "map"
                ? "w-full"
                : "hidden lg:block lg:w-1/2 xl:w-2/5"
            }>
              <div
                className={`sticky top-36 rounded-xl overflow-hidden border border-border shadow-card ${
                  viewMode === "map" ? "h-[calc(100vh-14rem)]" : "h-[calc(100vh-10rem)]"
                }`}
              >
                <div className="relative h-full min-h-0">
                  <PropertyMap
                    properties={mapProperties}
                    activeId={activeMarker}
                    onMarkerClick={(id) => setActiveMarker(id)}
                    regionSlug={parsed.regionSlug ?? "minsk"}
                    citySlug={parsed.citySlug}
                  />
                  {viewMode === "map" && !isLoading && displayProperties.length === 0 && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center pointer-events-none bg-background/65 backdrop-blur-[1px]">
                      <div className="bg-card rounded-xl p-6 shadow-card-hover text-center pointer-events-auto max-w-sm">
                        <h3 className="text-lg font-display font-semibold text-foreground mb-2">Ничего не найдено</h3>
                        <p className="text-sm text-muted-foreground mb-4">Будьте первым — разместите объявление здесь.</p>
                        <div className="flex flex-col gap-2">
                          <Button size="default" asChild className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0">
                            <Link href="/razmestit/">
                              <Plus className="w-4 h-4 mr-1.5" />
                              Подать объявление
                            </Link>
                          </Button>
                          <Button variant="outline" onClick={clearFilters}>Сбросить фильтры</Button>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}
        </div>
      </section>
    </div>
  );
}
