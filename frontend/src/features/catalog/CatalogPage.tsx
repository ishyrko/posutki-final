'use client';

import { useEffect, useMemo, useState, type ReactNode } from "react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { ListingSubmitLink } from "@/components/ListingSubmitLink";
import {
  SlidersHorizontal,
  X,
  Map as MapIcon,
  LayoutGrid,
  Rows3,
  ChevronLeft,
  ChevronRight,
  Plus,
  ChevronDown,
  Wifi,
  Snowflake,
  Tv,
  WashingMachine,
  ChefHat,
  Bath,
  Car,
  Wind,
  Flame,
  Waves,
  Droplets,
  Wallet,
  CreditCard,
  Banknote,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import PropertyListCard from "@/components/PropertyListCard";
import PropertyCard from "@/components/PropertyCard";
import PropertyMap, { type MapProperty } from "@/components/PropertyMap";
import { useProperties, useExchangeRates } from "@/features/properties/hooks";
import { useMetroStations } from "@/features/metro/hooks";
import type { NearbyMetroStation } from "@/features/metro/types";
import { Property, formatAddress, type PriceType, type Currency } from "@/features/properties/types";
import { useCurrency } from "@/context/CurrencyContext";
import type { ExchangeRates } from "@/features/properties/api";
import {
  formatPropertyPrices,
  DEFAULT_EXCHANGE_RATES_FALLBACK,
} from "@/features/properties/price-display";
import {
  buildPageTitle,
  isMetroCatalogContext,
  propertyUrlRegionSlug,
  type ParsedSegments,
} from "@/features/catalog/slugs";
import { GuestCountControl } from "@/features/catalog/GuestCountControl";
import {
  AMENITY_QUERY_PARAM,
  parseAmenitiesFromQuery,
} from "@/features/catalog/amenities-filter";
import {
  clampGuests,
  GUESTS_QUERY_PARAM,
  parseGuestsFromQuery,
} from "@/features/catalog/guests-filter";
import { showBathrooms, showRooms, showRoomsCatalogFilter } from "@/features/create-listing/property-field-rules";
import { PAYMENT_METHOD_OPTIONS } from "@/features/properties/payment-methods";
import { PriceDisplay } from "@/components/BynCurrency";
import { cn } from "@/lib/utils";
import { useMinWidth } from "@/hooks/use-min-width";

type ViewMode = "grid" | "list" | "map";

/** Горизонтальный список (PropertyListCard) рассчитан на широкую колонку результатов. */
const CATALOG_LIST_VIEW_MIN_WIDTH = 1100;

/** Пустая строка — без фильтра по комнатам. */
const roomCountOptions = [
  { value: "1", label: "1" },
  { value: "2", label: "2" },
  { value: "3", label: "3" },
  { value: "4", label: "4+" },
] as const;

type RoomBucket = (typeof roomCountOptions)[number]["value"];

function sortRoomBuckets(a: RoomBucket, b: RoomBucket): number {
  return Number(a) - Number(b);
}

function parseRoomsFromQuery(raw: string | null): RoomBucket[] {
  if (!raw) return [];
  const set = new Set<RoomBucket>();
  for (const part of raw.split(",")) {
    const p = part.trim();
    if (p === "3+") {
      set.add("3");
      set.add("4");
      continue;
    }
    if (p === "1" || p === "2" || p === "3") set.add(p);
    if (p === "4" || p === "4+") set.add("4");
  }
  return [...set].sort(sortRoomBuckets);
}

const sortOptions = [
  { value: "default", label: "По умолчанию" },
  { value: "price-asc", label: "Цена: по возрастанию" },
  { value: "price-desc", label: "Цена: по убыванию" },
];

const viewModes: { value: ViewMode; icon: typeof LayoutGrid; title: string }[] = [
  { value: "grid", icon: LayoutGrid, title: "Плитка" },
  { value: "list", icon: Rows3, title: "Список" },
  { value: "map", icon: MapIcon, title: "Карта" },
];

const mobileViewModes = viewModes.filter((m) => m.value === "grid" || m.value === "map");

/** Каталог фильтрует удобства на клиенте (текущая страница выдачи); id совпадают с шагом размещения. */
const CATALOG_AMENITY_OPTIONS: {
  id: string;
  label: string;
  icon: typeof Wifi;
  matches: (amenityIds: string[]) => boolean;
}[] = [
  { id: "wifi", label: "Wi-Fi", icon: Wifi, matches: (ids) => ids.includes("wifi") },
  { id: "ac", label: "Кондиционер", icon: Snowflake, matches: (ids) => ids.includes("air_conditioner") },
  {
    id: "tv",
    label: "Телевизор",
    icon: Tv,
    matches: (ids) => ["smart_tv", "tv", "projector", "cable_tv"].some((x) => ids.includes(x)),
  },
  { id: "washer", label: "Стиральная машина", icon: WashingMachine, matches: (ids) => ids.includes("washing_machine") },
  {
    id: "kitchen",
    label: "Кухня",
    icon: ChefHat,
    matches: (ids) =>
      [
        "fridge",
        "electric_stove",
        "gas_stove",
        "induction_stove",
        "oven",
        "microwave",
        "dishwasher",
        "coffee_machine",
        "kettle",
        "blender",
        "dishes_utensils",
      ].some((x) => ids.includes(x)),
  },
  { id: "dishwasher", label: "Посудомоечная", icon: WashingMachine, matches: (ids) => ids.includes("dishwasher") },
  { id: "jacuzzi", label: "Джакузи", icon: Bath, matches: (ids) => ids.includes("jacuzzi") },
  {
    id: "parking",
    label: "Паркинг",
    icon: Car,
    matches: (ids) => ids.some((x) => x.includes("parking") || x.includes("garage")),
  },
  { id: "dryer", label: "Сушилка", icon: Wind, matches: (ids) => ids.includes("dryer") },
  { id: "sauna", label: "Баня / сауна", icon: Flame, matches: (ids) => ids.includes("sauna") },
  { id: "pool", label: "Бассейн", icon: Waves, matches: (ids) => ids.includes("pool") },
  { id: "pond", label: "Пруд", icon: Droplets, matches: (ids) => ids.includes("pond") },
];

const CATALOG_PAYMENT_ICONS: Record<string, typeof Wallet> = {
  cash: Banknote,
  card: CreditCard,
  bank_transfer: Wallet,
};

function foundCountLabel(n: number, loading: boolean): ReactNode {
  if (loading) {
    return <span className="text-muted-foreground">Загрузка...</span>;
  }
  const mod10 = n % 10;
  const mod100 = n % 100;
  let word = "вариантов";
  if (mod10 === 1 && mod100 !== 11) word = "вариант";
  else if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) word = "варианта";
  return (
    <>
      Найдено <span className="font-semibold text-foreground">{n}</span> {word}
    </>
  );
}

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

function propertyToListCard(p: Property, rates: ExchangeRates, metroFilterStationId: number | null, displayCurrency: Currency = "BYN") {
  const { primaryAmount, primaryCurrency, secondary } = formatPropertyPrices(p, rates, displayCurrency);
  return {
    image: p.images?.[0]?.thumbnailUrl || p.images?.[0]?.url || "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800",
    price: <PriceDisplay amount={primaryAmount} currency={primaryCurrency} />,
    primaryBynAmount: primaryAmount,
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
    regionSlug: propertyUrlRegionSlug(p.address.regionName, p.address.citySlug, p.type),
    nearbyMetroStations: pickMetroStationsForCatalog(p.nearbyMetroStations ?? [], metroFilterStationId),
  };
}

function propertyToMapItem(p: Property, rates: ExchangeRates, displayCurrency: Currency = "BYN"): MapProperty | null {
  if (!p.coordinates?.latitude || !p.coordinates?.longitude) return null;
  const { primaryAmount, primaryPlain } = formatPropertyPrices(p, rates, displayCurrency);
  return {
    id: p.id,
    lat: p.coordinates.latitude,
    lng: p.coordinates.longitude,
    title: p.title,
    price: primaryPlain,
    address: formatAddress(p.address),
    image: p.images?.[0]?.thumbnailUrl || p.images?.[0]?.url || "",
    dealType: p.dealType,
    propertyType: p.type,
    regionSlug: propertyUrlRegionSlug(p.address.regionName, p.address.citySlug, p.type),
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
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const [priceType, setPriceType] = useState<PriceType>("total");
  const { selectedCurrency } = useCurrency();
  const [roomBuckets, setRoomBuckets] = useState<RoomBucket[]>([]);
  const [metroStationId, setMetroStationId] = useState("all");
  const [nearMetro, setNearMetro] = useState(false);
  const [sort, setSort] = useState("default");
  const [showMobileFilters, setShowMobileFilters] = useState(false);
  const [showAllAmenities, setShowAllAmenities] = useState(false);
  const [selectedAmenityIds, setSelectedAmenityIds] = useState<string[]>([]);
  const [selectedPaymentMethodIds, setSelectedPaymentMethodIds] = useState<string[]>([]);
  const [viewMode, setViewMode] = useState<ViewMode>("grid");
  const listViewAvailable = useMinWidth(CATALOG_LIST_VIEW_MIN_WIDTH);
  const visibleViewModes = useMemo(
    () => viewModes.filter((m) => m.value !== "list" || listViewAvailable),
    [listViewAvailable],
  );
  const [activeMarker, setActiveMarker] = useState<number | null>(null);
  const isSaleDeal = false;
  const pageFromQuery = Number(searchParams.get("page") ?? "1");
  const validPageFromQuery = Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const [currentPage, setCurrentPage] = useState(validPageFromQuery);
  const itemsPerPage = 12;
  const metroFilterVisible = isMetroCatalogContext(parsed);
  const { data: metroStations = [] } = useMetroStations(1, metroFilterVisible);
  const roomsFilterVisible = showRoomsCatalogFilter(parsed.propertyType);

  useEffect(() => {
    setCurrentPage(validPageFromQuery);
  }, [validPageFromQuery]);

  useEffect(() => {
    if (!listViewAvailable && viewMode === "list") {
      setViewMode("grid");
    }
  }, [listViewAvailable, viewMode]);

  const roomsFromQuery = searchParams.get("rooms");
  const guestsFromQuery = parseGuestsFromQuery(searchParams.get(GUESTS_QUERY_PARAM));
  const amenitiesFromQuery = searchParams.get(AMENITY_QUERY_PARAM);

  useEffect(() => {
    if (!roomsFilterVisible) return;
    setRoomBuckets(parseRoomsFromQuery(roomsFromQuery));
  }, [roomsFromQuery, roomsFilterVisible]);

  useEffect(() => {
    const ids = parseAmenitiesFromQuery(amenitiesFromQuery);
    setSelectedAmenityIds(ids);
    if (
      ids.some((id) => {
        const idx = CATALOG_AMENITY_OPTIONS.findIndex((o) => o.id === id);
        return idx >= 4;
      })
    ) {
      setShowAllAmenities(true);
    }
  }, [amenitiesFromQuery]);

  useEffect(() => {
    if (!roomsFilterVisible) {
      setRoomBuckets([]);
    }
  }, [roomsFilterVisible]);

  useEffect(() => {
    if (!metroFilterVisible) {
      setNearMetro(false);
      setMetroStationId("all");
    }
  }, [metroFilterVisible]);

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
  const activeFilterCount =
    (hasPriceFilter ? 1 : 0) +
    (guestsFromQuery !== null ? 1 : 0) +
    (roomsFilterVisible && roomBuckets.length > 0 ? 1 : 0) +
    (metroFilterVisible && nearMetro && metroStationId !== "all" ? 1 : 0) +
    (metroFilterVisible && nearMetro ? 1 : 0) +
    selectedAmenityIds.length +
    selectedPaymentMethodIds.length;

  const pageTitle = useMemo(() => {
    if (!parsed.metroStationSlug) return title;
    const station = metroStations.find((s) => s.slug === parsed.metroStationSlug);
    return station ? buildPageTitle(parsed, undefined, station.name) : title;
  }, [parsed, metroStations, title]);

  /** Same station id as sent to the API when filtering by metro (URL or sidebar). */
  const metroFilterStationId = useMemo((): number | null => {
    if (!metroFilterVisible) return null;
    const routeMetroStation = parsed.metroStationSlug
      ? metroStations.find((station) => station.slug === parsed.metroStationSlug)
      : undefined;
    if (routeMetroStation) return routeMetroStation.id;
    if (nearMetro && metroStationId !== "all") return Number(metroStationId);
    return null;
  }, [metroFilterVisible, parsed.metroStationSlug, metroStations, nearMetro, metroStationId]);

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
    if (parsed.citySlug) {
      f.citySlug = parsed.citySlug;
    } else if (parsed.regionSlug) {
      f.regionSlug = parsed.regionSlug;
    } else {
      f.regionSlug = "minsk";
    }
    if (parsed.propertyType) f.type = parsed.propertyType;
    if (roomsFilterVisible && roomBuckets.length > 0) {
      f.roomValues = roomBuckets.map((b) => (b === "4" ? 4 : Number(b)));
    }
    if (metroFilterVisible) {
      if (routeMetroStation) {
        f.metroStationId = routeMetroStation.id;
      } else if (nearMetro && metroStationId !== "all") {
        f.metroStationId = Number(metroStationId);
      }
      if (parsed.nearMetro || nearMetro) {
        f.nearMetro = true;
      }
    }
    if (minPrice) f.minPrice = Number(minPrice);
    if (maxPrice) f.maxPrice = Number(maxPrice);
    if (guestsFromQuery !== null) f.guests = guestsFromQuery;
    if (isSaleDeal && hasPriceFilter && priceType !== "total") f.priceType = priceType;
    if (hasPriceFilter) f.currency = selectedCurrency;
    if (sort === "price-asc") { f.sortBy = "price"; f.sortOrder = "ASC"; }
    else if (sort === "price-desc") { f.sortBy = "price"; f.sortOrder = "DESC"; }
    return f;
  }, [currentPage, parsed.dealType, parsed.regionSlug, parsed.propertyType, parsed.citySlug, parsed.nearMetro, parsed.metroStationSlug, metroFilterVisible, metroStations, roomsFilterVisible, roomBuckets, metroStationId, nearMetro, minPrice, maxPrice, guestsFromQuery, priceType, selectedCurrency, hasPriceFilter, isSaleDeal, sort]);

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

  const setGuestsFilter = (count: number) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set(GUESTS_QUERY_PARAM, String(clampGuests(count)));
    params.delete("page");
    setCurrentPage(1);
    const query = params.toString();
    router.replace(query ? `${pathname}?${query}` : pathname);
  };

  const toggleCatalogAmenity = (id: string) => {
    setSelectedAmenityIds((prev) =>
      prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id],
    );
    resetToFirstPage();
  };

  const toggleCatalogPaymentMethod = (id: string) => {
    setSelectedPaymentMethodIds((prev) =>
      prev.includes(id) ? prev.filter((m) => m !== id) : [...prev, id],
    );
    resetToFirstPage();
  };

  const displayProperties = useMemo(() => {
    let list = properties;
    if (selectedAmenityIds.length > 0) {
      list = list.filter((p) => {
        const ids = p.amenities ?? [];
        return selectedAmenityIds.every((selId) => {
          const opt = CATALOG_AMENITY_OPTIONS.find((o) => o.id === selId);
          return opt ? opt.matches(ids) : false;
        });
      });
    }
    if (selectedPaymentMethodIds.length > 0) {
      list = list.filter((p) => {
        const methods = p.specifications.paymentMethods ?? [];
        return selectedPaymentMethodIds.every((selId) => methods.includes(selId));
      });
    }
    return list;
  }, [properties, selectedAmenityIds, selectedPaymentMethodIds]);

  const mapProperties: MapProperty[] = useMemo(() => {
    return displayProperties
      .map((p) => propertyToMapItem(p, exchangeRates, selectedCurrency))
      .filter((m): m is MapProperty => m !== null);
  }, [displayProperties, exchangeRates, selectedCurrency]);

  const clearFilters = () => {
    setMinPrice("");
    setMaxPrice("");
    setPriceType("total");
    setRoomBuckets([]);
    setMetroStationId("all");
    setNearMetro(false);
    setSelectedAmenityIds([]);
    setSelectedPaymentMethodIds([]);
    setShowAllAmenities(false);
    const params = new URLSearchParams(searchParams.toString());
    params.delete(GUESTS_QUERY_PARAM);
    params.delete(AMENITY_QUERY_PARAM);
    params.delete("page");
    setCurrentPage(1);
    const query = params.toString();
    router.replace(query ? `${pathname}?${query}` : pathname);
  };

  const filterSurfaceInput =
    "h-10 rounded-lg bg-surface border-border text-sm tabular-nums focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary";

  const filterPriceNumberInput = cn(
    filterSurfaceInput,
    "[appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none",
  );

  const renderCatalogFilters = () => (
    <div className="space-y-5">
      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">
          Цена за сутки
        </label>
        <div className="flex w-full min-w-0 flex-nowrap items-center gap-2">
          <Input
            type="number"
            placeholder="от"
            value={minPrice}
            onChange={(e) => { setMinPrice(e.target.value); resetToFirstPage(); }}
            className={cn(filterPriceNumberInput, "min-w-0 w-0 flex-1 basis-0")}
          />
          <span className="shrink-0 text-muted-foreground">—</span>
          <Input
            type="number"
            placeholder="до"
            value={maxPrice}
            onChange={(e) => { setMaxPrice(e.target.value); resetToFirstPage(); }}
            className={cn(filterPriceNumberInput, "min-w-0 w-0 flex-1 basis-0")}
          />
          {isSaleDeal && (
            <div className="flex h-10 shrink-0 overflow-hidden rounded-lg border border-border">
              <button
                type="button"
                onClick={() => setPriceType("total")}
                className={cn(
                  "cursor-pointer px-2 text-xs font-medium transition-colors sm:px-3 sm:text-sm",
                  priceType === "total" ? "bg-primary text-primary-foreground" : "bg-surface text-muted-foreground hover:bg-muted",
                )}
              >
                всего
              </button>
              <button
                type="button"
                onClick={() => setPriceType("perMeter")}
                className={cn(
                  "cursor-pointer border-l border-border px-2 text-xs font-medium transition-colors sm:px-3 sm:text-sm",
                  priceType === "perMeter" ? "bg-primary text-primary-foreground" : "bg-surface text-muted-foreground hover:bg-muted",
                )}
              >
                за м²
              </button>
            </div>
          )}
        </div>
      </div>

      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Гости</label>
        <div className="rounded-lg border border-border bg-surface px-3 py-2">
          <GuestCountControl
            id="catalog-guests"
            value={guestsFromQuery ?? 2}
            onChange={setGuestsFilter}
            showIcon={false}
            hideLabel
          />
        </div>
      </div>

      {roomsFilterVisible && (
        <div>
          <label className="text-sm font-semibold text-foreground mb-2 block font-display">Комнаты</label>
          <div className="grid grid-cols-4 gap-2">
            {roomCountOptions.map((r) => (
              <button
                key={r.value}
                type="button"
                onClick={() => {
                  setRoomBuckets((prev) => {
                    if (prev.includes(r.value)) {
                      return prev.filter((x) => x !== r.value).sort(sortRoomBuckets);
                    }
                    return [...prev, r.value].sort(sortRoomBuckets);
                  });
                  resetToFirstPage();
                }}
                className={cn(
                  "min-w-0 cursor-pointer rounded-lg border px-2 py-2 text-center text-sm font-medium transition-all duration-150",
                  roomBuckets.includes(r.value)
                    ? "border-primary bg-primary text-primary-foreground shadow-sm"
                    : "border-border bg-surface text-foreground hover:bg-muted",
                )}
              >
                {r.label}
              </button>
            ))}
          </div>
        </div>
      )}

      {metroFilterVisible && (
      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Станция метро</label>
        <Select value={metroStationId} onValueChange={setMetroStationId} disabled={!nearMetro}>
          <SelectTrigger
            className={cn(
              filterSurfaceInput,
              "w-full cursor-pointer justify-between gap-2 text-left disabled:cursor-not-allowed [&>span]:min-w-0 [&>span]:truncate",
            )}
          >
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
        <label className="mt-3 inline-flex items-center gap-2 text-sm text-foreground/80 cursor-pointer">
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
      )}

      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Удобства</label>
        <div className="grid grid-cols-1 gap-2">
          {(showAllAmenities ? CATALOG_AMENITY_OPTIONS : CATALOG_AMENITY_OPTIONS.slice(0, 4)).map((opt) => {
            const Icon = opt.icon;
            const active = selectedAmenityIds.includes(opt.id);
            return (
              <button
                key={opt.id}
                type="button"
                onClick={() => toggleCatalogAmenity(opt.id)}
                className={cn(
                  "flex cursor-pointer items-center gap-2 px-3 py-2 rounded-lg text-left text-xs font-medium transition-all duration-150",
                  active
                    ? "bg-primary/10 border-primary text-primary border"
                    : "border border-border bg-surface text-foreground hover:bg-muted",
                )}
              >
                <Icon className="h-3.5 w-3.5 shrink-0" />
                <span className="truncate">{opt.label}</span>
              </button>
            );
          })}
        </div>
        {CATALOG_AMENITY_OPTIONS.length > 4 && (
          <button
            type="button"
            onClick={() => setShowAllAmenities((v) => !v)}
            className="mt-2 inline-flex cursor-pointer items-center gap-1 text-xs font-semibold text-primary transition-colors hover:text-primary/80"
          >
            {showAllAmenities ? "Свернуть" : `Показать ещё ${CATALOG_AMENITY_OPTIONS.length - 4}`}
            <ChevronDown className={cn("h-3.5 w-3.5 transition-transform", showAllAmenities && "rotate-180")} />
          </button>
        )}
      </div>

      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Способы оплаты</label>
        <div className="grid grid-cols-1 gap-2">
          {PAYMENT_METHOD_OPTIONS.map((opt) => {
            const Icon = CATALOG_PAYMENT_ICONS[opt.id] ?? Wallet;
            const active = selectedPaymentMethodIds.includes(opt.id);
            return (
              <button
                key={opt.id}
                type="button"
                onClick={() => toggleCatalogPaymentMethod(opt.id)}
                className={cn(
                  "flex cursor-pointer items-center gap-2 px-3 py-2 rounded-lg text-left text-xs font-medium transition-all duration-150",
                  active
                    ? "bg-primary/10 border-primary text-primary border"
                    : "border border-border bg-surface text-foreground hover:bg-muted",
                )}
              >
                <Icon className="h-3.5 w-3.5 shrink-0" />
                <span className="truncate">{opt.label}</span>
              </button>
            );
          })}
        </div>
      </div>

      {activeFilterCount > 0 && (
        <button
          type="button"
          onClick={clearFilters}
          className="cursor-pointer text-sm font-medium text-destructive transition-colors hover:text-destructive/80"
        >
          Сбросить фильтры
        </button>
      )}
    </div>
  );

  const resultsBottomPadding = viewMode !== "map" ? "pb-8" : "pb-6";

  const catalogResultCount =
    selectedAmenityIds.length > 0 || selectedPaymentMethodIds.length > 0
      ? displayProperties.length
      : totalItems;

  return (
    <div className="min-h-screen bg-background">
      {showMobileFilters && (
        <div className="md:hidden bg-card border-b border-border animate-fade-in">
          <div className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-display font-semibold text-foreground">Фильтры</h3>
              <button type="button" onClick={() => setShowMobileFilters(false)} aria-label="Закрыть">
                <X className="h-5 w-5 text-muted-foreground" />
              </button>
            </div>
            {renderCatalogFilters()}
            <div className="mt-5 space-y-4">
              <div>
                <label className="text-sm font-semibold text-foreground mb-2 block font-display">Сортировка</label>
                <Select value={sort} onValueChange={setSort}>
                  <SelectTrigger className={cn(filterSurfaceInput, "w-full cursor-pointer")}>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent className="bg-card border-border z-50">
                    {sortOptions.map((o) => (
                      <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <label className="text-sm font-semibold text-foreground mb-2 block font-display">Вид</label>
                <div className="grid grid-cols-2 gap-2">
                  {mobileViewModes.map((m) => (
                    <button
                      key={m.value}
                      type="button"
                      onClick={() => { setViewMode(m.value); setShowMobileFilters(false); }}
                      className={cn(
                        "flex cursor-pointer flex-col items-center justify-center gap-1 py-2.5 rounded-lg text-xs font-medium transition-all",
                        viewMode === m.value
                          ? "bg-primary text-primary-foreground"
                          : "bg-surface text-foreground border border-border",
                      )}
                    >
                      <m.icon className="h-4 w-4" />
                      {m.title}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      <section className={cn("container mx-auto px-4 py-8", resultsBottomPadding)}>
        <div className="flex gap-8">
          <aside className="hidden md:block w-64 shrink-0">
            <div className="sticky top-36 bg-card rounded-xl p-5 shadow-card">
              <div className="flex items-center justify-between mb-5">
                <h3 className="font-display font-semibold text-foreground flex items-center gap-2">
                  <SlidersHorizontal className="h-4 w-4" />
                  Фильтры
                </h3>
                {activeFilterCount > 0 && (
                  <span className="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full">
                    {activeFilterCount}
                  </span>
                )}
              </div>
              {renderCatalogFilters()}
            </div>
          </aside>

          <div className="flex-1 min-w-0">
            <div className="mb-5">
              <h1 className="font-display font-bold text-2xl md:text-3xl text-foreground tracking-tight">
                {pageTitle}
              </h1>
            </div>

            <div className="flex items-center justify-between gap-3 mb-6 flex-wrap">
              <p className="text-sm text-muted-foreground">
                {foundCountLabel(catalogResultCount, isLoading)}
              </p>

              <div className="flex items-center gap-3 ml-auto">
                <div className="flex md:hidden items-center bg-surface border border-border rounded-xl overflow-hidden shrink-0">
                  {mobileViewModes.map((m, i) => (
                    <button
                      key={m.value}
                      type="button"
                      title={m.title}
                      aria-label={m.title}
                      onClick={() => setViewMode(m.value)}
                      className={cn(
                        "cursor-pointer p-2.5 transition-all duration-150",
                        i > 0 && "border-l border-border",
                        viewMode === m.value
                          ? "bg-primary text-primary-foreground"
                          : "text-muted-foreground hover:text-foreground",
                      )}
                    >
                      <m.icon className="h-4 w-4" />
                    </button>
                  ))}
                </div>
                <div className="hidden md:flex items-center bg-surface border border-border rounded-xl overflow-hidden shrink-0">
                  {visibleViewModes.map((m, i) => (
                    <button
                      key={m.value}
                      type="button"
                      title={m.title}
                      aria-label={m.title}
                      onClick={() => setViewMode(m.value)}
                      className={cn(
                        "cursor-pointer p-2.5 transition-all duration-150",
                        i > 0 && "border-l border-border",
                        viewMode === m.value
                          ? "bg-primary text-primary-foreground"
                          : "text-muted-foreground hover:text-foreground",
                      )}
                    >
                      <m.icon className="h-4 w-4" />
                    </button>
                  ))}
                </div>

                <div className="hidden md:block shrink-0">
                  <Select value={sort} onValueChange={setSort}>
                    <SelectTrigger className="h-auto min-h-0 cursor-pointer rounded-xl border-border bg-surface py-2.5 pl-3 pr-8 text-sm shadow-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-0">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-card border-border z-50">
                      {sortOptions.map((o) => (
                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <Button
                  variant="outline"
                  size="sm"
                  className="md:hidden gap-2 shrink-0"
                  onClick={() => setShowMobileFilters(!showMobileFilters)}
                >
                  <SlidersHorizontal className="h-4 w-4" />
                  Фильтры
                  {activeFilterCount > 0 && (
                    <span className="ml-1 w-5 h-5 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center font-semibold">
                      {activeFilterCount}
                    </span>
                  )}
                </Button>
              </div>
            </div>

            {viewMode === "map" && isLoading && (
              <div className="h-[calc(100vh-220px)] min-h-[500px] rounded-xl bg-muted animate-pulse border border-border" />
            )}

            {viewMode === "map" && !isLoading && displayProperties.length === 0 && (
              <div className="h-[calc(100vh-220px)] min-h-[500px] flex items-center justify-center bg-surface rounded-xl border border-border">
                <div className="text-center px-4">
                  <MapIcon className="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                  <h3 className="font-display font-semibold text-foreground mb-1">Нет результатов на карте</h3>
                  <p className="text-sm text-muted-foreground mb-3">Попробуйте изменить фильтры</p>
                  <Button variant="outline" size="sm" onClick={clearFilters}>
                    Сбросить фильтры
                  </Button>
                </div>
              </div>
            )}

            {viewMode === "map" && !isLoading && displayProperties.length > 0 && (
              <div className="h-[calc(100vh-220px)] min-h-[500px] rounded-xl overflow-hidden border border-border shadow-card">
                <div className="relative h-full min-h-0">
                  <PropertyMap
                    properties={mapProperties}
                    activeId={activeMarker}
                    onMarkerClick={(id) => setActiveMarker(id)}
                    regionSlug={parsed.regionSlug ?? "minsk"}
                    citySlug={parsed.citySlug}
                  />
                </div>
              </div>
            )}

            {viewMode === "grid" && (
              <>
                {isLoading ? (
                  <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    {[...Array(itemsPerPage)].map((_, i) => (
                      <div key={i} className="h-[360px] bg-muted/50 animate-pulse rounded-xl" />
                    ))}
                  </div>
                ) : displayProperties.length > 0 ? (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                      {displayProperties.map((property, i) => {
                        const { primaryAmount, primaryCurrency, secondary } = formatPropertyPrices(property, exchangeRates, selectedCurrency);
                        return (
                          <PropertyCard
                            key={property.id}
                            id={property.id}
                            image={property.images?.[0]?.thumbnailUrl || property.images?.[0]?.url || "https://placehold.co/600x450?text=No+Image"}
                            price={<PriceDisplay amount={primaryAmount} currency={primaryCurrency} />}
                            primaryBynAmount={primaryAmount}
                            secondaryPrice={secondary}
                            title={property.title}
                            address={formatAddress(property.address)}
                            beds={property.specifications.rooms || 0}
                            baths={property.specifications.bathrooms ?? 1}
                            area={property.specifications.area || 0}
                            maxGuests={property.specifications.maxDailyGuests}
                            dealType={property.dealType}
                            propertyType={property.type}
                            regionSlug={propertyUrlRegionSlug(property.address.regionName, property.address.citySlug, property.type)}
                            typeLabel={property.typeLabel}
                            index={i}
                            animateEntrance={false}
                            rating={property.ratingAvg ?? null}
                            reviewCount={property.reviewCount ?? null}
                          />
                        );
                      })}
                    </div>
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
                    <div className="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mx-auto mb-4">
                      <SlidersHorizontal className="h-7 w-7 text-muted-foreground" />
                    </div>
                    <h3 className="font-display font-semibold text-foreground text-lg mb-2">
                      Ничего не найдено
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      Попробуйте изменить параметры поиска
                    </p>
                    <p className="text-sm text-muted-foreground mb-6">
                      Или будьте первым, кто разместит объявление в этом разделе.
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
                      <Button size="default" asChild className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0">
                        <ListingSubmitLink>
                          <Plus className="w-4 h-4 mr-1.5" />
                          Подать объявление
                        </ListingSubmitLink>
                      </Button>
                      <Button variant="outline" onClick={clearFilters}>Сбросить фильтры</Button>
                    </div>
                  </div>
                )}
              </>
            )}

            {viewMode === "list" && (
              <>
                {isLoading ? (
                  <div className="flex flex-col gap-4">
                    {[...Array(3)].map((_, i) => (
                      <div key={i} className="h-48 bg-muted/50 animate-pulse rounded-xl" />
                    ))}
                  </div>
                ) : displayProperties.length > 0 ? (
                  <>
                    <div className="flex flex-col gap-4">
                      {displayProperties.map((property, i) => {
                        const card = propertyToListCard(property, exchangeRates, metroFilterStationId, selectedCurrency);
                        return (
                          <div
                            key={property.id}
                            onMouseEnter={() => setActiveMarker(property.id)}
                            onMouseLeave={() => setActiveMarker(null)}
                          >
                            <PropertyListCard
                              {...card}
                              index={i}
                              metroOnSeparateLine={false}
                            />
                          </div>
                        );
                      })}
                    </div>
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
                    <div className="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mx-auto mb-4">
                      <SlidersHorizontal className="h-7 w-7 text-muted-foreground" />
                    </div>
                    <h3 className="font-display font-semibold text-foreground text-lg mb-2">
                      Ничего не найдено
                    </h3>
                    <p className="text-muted-foreground mb-4">
                      Попробуйте изменить параметры поиска
                    </p>
                    <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
                      <Button size="default" asChild className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0">
                        <ListingSubmitLink>
                          <Plus className="w-4 h-4 mr-1.5" />
                          Подать объявление
                        </ListingSubmitLink>
                      </Button>
                      <Button variant="outline" onClick={clearFilters}>Сбросить фильтры</Button>
                    </div>
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      </section>

    </div>
  );
}
