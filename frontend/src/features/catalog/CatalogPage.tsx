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
import { useCityDistricts } from "@/features/city-districts/hooks";
import { useCityLandmarks } from "@/features/landmarks/hooks";
import type { Landmark } from "@/features/landmarks/types";
import {
  formatLandmarkDistance,
  DEFAULT_LANDMARK_MAX_DISTANCE_KM,
  LANDMARK_DISTANCE_FILTER_OPTIONS,
  type LandmarkDistanceFilterValue,
} from "@/features/landmarks/distance";
import CatalogLandmarkBanner from "@/features/catalog/CatalogLandmarkBanner";
import CatalogLandmarkDetails from "@/features/catalog/CatalogLandmarkDetails";
import CatalogCitySeoSection from "@/features/catalog/CatalogCitySeoSection";
import type { LandmarkListItem } from "@/features/landmarks/types";
import type { MetroStation, NearbyMetroStation } from "@/features/metro/types";
import { Property, formatAddress, type Currency } from "@/features/properties/types";
import { useCurrency } from "@/context/CurrencyContext";
import type { ExchangeRates } from "@/features/properties/api";
import {
  formatPropertyPrices,
  DEFAULT_EXCHANGE_RATES_FALLBACK,
} from "@/features/properties/price-display";
import {
  buildCatalogUrl,
  buildPageTitle,
  CITY_PREFIX_SLUGS,
  isDistrictCatalogContext,
  isLandmarkCatalogContext,
  isMetroCatalogContext,
  isNearMetroLandingPage,
  NEAR_METRO_CATALOG_INTRO,
  propertyUrlRegionSlug,
  REGION_SLUGS,
  resolveCatalogCitySlug,
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

const CATALOG_ITEMS_PER_PAGE = 48;
/** В режиме карты нужны все точки по фильтрам, не одна страница списка. */
const CATALOG_MAP_FETCH_LIMIT = 500;
/** Стабильная ссылка: `data ?? []` в деструктуризации даёт новый массив на каждый рендер. */
const EMPTY_METRO_STATIONS: MetroStation[] = [];

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

/** Каталог фильтрует удобства и способы оплаты на клиенте; id совпадают с шагом размещения. */
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
    address: formatAddress(p.address, { includeCityDistrict: false }),
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
    address: formatAddress(p.address, { includeCityDistrict: false }),
    image: p.images?.[0]?.thumbnailUrl || p.images?.[0]?.url || "",
    dealType: p.dealType,
    propertyType: p.type,
    regionSlug: propertyUrlRegionSlug(p.address.regionName, p.address.citySlug, p.type),
  };
}

interface CatalogLandmarkFooterProps {
  landmark: Landmark;
  citySlug: string;
  relatedLandmarks: LandmarkListItem[];
  descriptionHtml?: string | null;
}

interface CatalogCitySeoFooterProps {
  heading: string;
  html: string;
}

interface CatalogPageProps {
  parsed: ParsedSegments;
  title: string;
  landmark?: Landmark | null;
  /** Pre-rendered on the server in page.tsx; passed as props to avoid RSC children in this client tree (Radix useId). */
  landmarkFooter?: CatalogLandmarkFooterProps | null;
  citySeoFooter?: CatalogCitySeoFooterProps | null;
  children?: ReactNode;
}

export default function CatalogPage({
  parsed,
  title,
  landmark,
  landmarkFooter = null,
  citySeoFooter = null,
  children,
}: CatalogPageProps) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const isLandmarkPage = Boolean(parsed.landmarkSlug);
  const [minPrice, setMinPrice] = useState("");
  const [maxPrice, setMaxPrice] = useState("");
  const { selectedCurrency } = useCurrency();
  const [roomBuckets, setRoomBuckets] = useState<RoomBucket[]>([]);
  const [metroStationId, setMetroStationId] = useState("all");
  const [nearMetro, setNearMetro] = useState(false);
  const [landmarkMaxDistanceKm, setLandmarkMaxDistanceKm] = useState<LandmarkDistanceFilterValue>(
    DEFAULT_LANDMARK_MAX_DISTANCE_KM,
  );
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
  const pageFromQuery = Number(searchParams.get("page") ?? "1");
  const validPageFromQuery = Number.isFinite(pageFromQuery) && pageFromQuery > 0 ? Math.floor(pageFromQuery) : 1;
  const [currentPage, setCurrentPage] = useState(validPageFromQuery);
  const metroFilterVisible = isMetroCatalogContext(parsed);
  const districtFilterVisible = isDistrictCatalogContext(parsed);
  const landmarkFilterVisible = isLandmarkCatalogContext(parsed);
  const catalogCitySlug = resolveCatalogCitySlug(parsed);
  const nearMetroLanding = isNearMetroLandingPage(parsed);
  const showMetroSidebarFilters = metroFilterVisible && !parsed.metroStationSlug;
  const showDistrictSidebarFilters = districtFilterVisible && !parsed.cityDistrictSlug;
  const showLandmarkSidebarFilters = landmarkFilterVisible && !parsed.landmarkSlug;
  const { data: metroStationsData } = useMetroStations(1, metroFilterVisible);
  const { data: cityDistrictsData } = useCityDistricts(catalogCitySlug, districtFilterVisible);
  const { data: cityLandmarksData } = useCityLandmarks(catalogCitySlug, landmarkFilterVisible);
  const metroStations = metroStationsData ?? EMPTY_METRO_STATIONS;
  const cityDistricts = cityDistrictsData ?? [];
  const cityLandmarks = cityLandmarksData ?? [];
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
  const catalogSortOptions = useMemo(
    () =>
      isLandmarkPage
        ? [
            { value: "distance-asc", label: "Сначала ближе" },
            ...sortOptions,
          ]
        : sortOptions,
    [isLandmarkPage],
  );

  const activeFilterCount =
    (hasPriceFilter ? 1 : 0) +
    (guestsFromQuery !== null ? 1 : 0) +
    (roomsFilterVisible && roomBuckets.length > 0 ? 1 : 0) +
    (isLandmarkPage && landmarkMaxDistanceKm !== DEFAULT_LANDMARK_MAX_DISTANCE_KM ? 1 : 0) +
    (showMetroSidebarFilters && !nearMetroLanding && nearMetro && metroStationId !== "all" ? 1 : 0) +
    (showMetroSidebarFilters && !nearMetroLanding && nearMetro ? 1 : 0) +
    selectedAmenityIds.length +
    selectedPaymentMethodIds.length;

  const pageTitle = useMemo(() => {
    if (parsed.cityDistrictSlug) {
      const district = cityDistricts.find((d) => d.slug === parsed.cityDistrictSlug);
      if (district) return buildPageTitle(parsed, undefined, undefined, district.name);
    }
    if (!parsed.metroStationSlug) return title;
    const station = metroStations.find((s) => s.slug === parsed.metroStationSlug);
    return station ? buildPageTitle(parsed, undefined, station.name) : title;
  }, [parsed, metroStations, cityDistricts, title]);

  /** Same station id as sent to the API when filtering by metro (URL or sidebar). */
  const metroFilterStationId = useMemo((): number | null => {
    if (!metroFilterVisible) return null;
    const routeMetroStation = parsed.metroStationSlug
      ? metroStations.find((station) => station.slug === parsed.metroStationSlug)
      : undefined;
    if (routeMetroStation) return routeMetroStation.id;
    if ((parsed.nearMetro || nearMetro) && metroStationId !== "all") return Number(metroStationId);
    return null;
  }, [metroFilterVisible, parsed.metroStationSlug, metroStations, nearMetro, metroStationId]);

  const hasClientOnlyFilters =
    selectedAmenityIds.length > 0 || selectedPaymentMethodIds.length > 0;
  const fetchAllForClientFilters = viewMode === "map" || hasClientOnlyFilters;

  const filters = useMemo(() => {
    const f: Record<string, unknown> = {
      page: fetchAllForClientFilters ? 1 : currentPage,
      limit: fetchAllForClientFilters ? CATALOG_MAP_FETCH_LIMIT : CATALOG_ITEMS_PER_PAGE,
      sortBy: "createdAt",
      sortOrder: "DESC" as const,
    };
    const routeMetroStation = parsed.metroStationSlug
      ? metroStations.find((station) => station.slug === parsed.metroStationSlug)
      : undefined;

    if (parsed.dealType) f.dealType = parsed.dealType;
    if (parsed.cityDistrictSlug) {
      f.citySlug = resolveCatalogCitySlug(parsed);
    } else if (parsed.landmarkSlug) {
      f.citySlug = resolveCatalogCitySlug(parsed);
      f.landmarkSlug = parsed.landmarkSlug;
      f.maxLandmarkDistanceKm = landmarkMaxDistanceKm;
    } else if (parsed.citySlug) {
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
      } else if ((parsed.nearMetro || nearMetro) && metroStationId !== "all") {
        f.metroStationId = Number(metroStationId);
      }
      if (parsed.nearMetro || nearMetro) {
        f.nearMetro = true;
      }
    }
    if (parsed.cityDistrictSlug) {
      f.cityDistrictSlug = parsed.cityDistrictSlug;
    }
    if (minPrice) f.minPrice = Number(minPrice);
    if (maxPrice) f.maxPrice = Number(maxPrice);
    if (guestsFromQuery !== null) f.guests = guestsFromQuery;
    if (hasPriceFilter) f.currency = selectedCurrency;
    if (sort === "distance-asc") { f.sortBy = "landmarkDistance"; f.sortOrder = "ASC"; }
    else if (sort === "price-asc") { f.sortBy = "price"; f.sortOrder = "ASC"; }
    else if (sort === "price-desc") { f.sortBy = "price"; f.sortOrder = "DESC"; }
    return f;
  }, [fetchAllForClientFilters, currentPage, parsed.dealType, parsed.regionSlug, parsed.propertyType, parsed.citySlug, parsed.cityDistrictSlug, parsed.landmarkSlug, parsed.nearMetro, parsed.metroStationSlug, metroFilterVisible, metroStations, roomsFilterVisible, roomBuckets, metroStationId, nearMetro, minPrice, maxPrice, guestsFromQuery, selectedCurrency, hasPriceFilter, sort, landmarkMaxDistanceKm]);

  const { data, isLoading } = useProperties(filters);
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const properties = useMemo(() => data?.data ?? [], [data?.data]);
  const totalItems = data?.meta?.total || 0;

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

  const catalogResultCount = hasClientOnlyFilters ? displayProperties.length : totalItems;
  const totalPages = Math.ceil(catalogResultCount / CATALOG_ITEMS_PER_PAGE);
  const effectivePage =
    totalPages > 0 ? Math.min(currentPage, totalPages) : 1;

  const paginatedDisplayProperties = useMemo(() => {
    if (!hasClientOnlyFilters || viewMode === "map") {
      return displayProperties;
    }
    const start = (effectivePage - 1) * CATALOG_ITEMS_PER_PAGE;
    return displayProperties.slice(start, start + CATALOG_ITEMS_PER_PAGE);
  }, [displayProperties, hasClientOnlyFilters, viewMode, effectivePage]);

  useEffect(() => {
    if (!hasClientOnlyFilters || isLoading) return;
    if (totalPages > 0 && currentPage > totalPages) {
      resetToFirstPage();
    }
  }, [hasClientOnlyFilters, isLoading, totalPages, currentPage]);

  const mapProperties: MapProperty[] = useMemo(() => {
    return displayProperties
      .map((p) => propertyToMapItem(p, exchangeRates, selectedCurrency))
      .filter((m): m is MapProperty => m !== null);
  }, [displayProperties, exchangeRates, selectedCurrency]);

  const clearFilters = () => {
    setMinPrice("");
    setMaxPrice("");
    setRoomBuckets([]);
    setMetroStationId("all");
    if (!nearMetroLanding) {
      setNearMetro(false);
    }
    setSelectedAmenityIds([]);
    setSelectedPaymentMethodIds([]);
    setShowAllAmenities(false);
    setLandmarkMaxDistanceKm(DEFAULT_LANDMARK_MAX_DISTANCE_KM);
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
      {isLandmarkPage && (
        <div>
          <label className="text-sm font-semibold text-foreground mb-2 block font-display">
            Расстояние до объекта
          </label>
          <div className="flex flex-wrap gap-2">
            {LANDMARK_DISTANCE_FILTER_OPTIONS.map((opt) => (
              <button
                key={opt.value}
                type="button"
                onClick={() => {
                  setLandmarkMaxDistanceKm(opt.value);
                  resetToFirstPage();
                }}
                className={cn(
                  "cursor-pointer rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150",
                  landmarkMaxDistanceKm === opt.value
                    ? "bg-primary text-primary-foreground shadow-sm"
                    : "border border-border bg-surface text-foreground hover:bg-muted",
                )}
              >
                {opt.label}
              </button>
            ))}
          </div>
        </div>
      )}

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

      {showMetroSidebarFilters && (
      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Станция метро</label>
        <Select
          value={metroStationId}
          onValueChange={(value) => {
            setMetroStationId(value);
            resetToFirstPage();
          }}
          disabled={!nearMetro && !nearMetroLanding}
        >
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
        {!nearMetroLanding && (
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
        )}
      </div>
      )}

      {showDistrictSidebarFilters && cityDistricts.length > 0 && (
      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Район</label>
        <Select
          value="all"
          onValueChange={(value) => {
            const isCityPrefix = CITY_PREFIX_SLUGS.has(catalogCitySlug);
            const url = buildCatalogUrl({
              region:
                !isCityPrefix && (parsed.regionSlug || REGION_SLUGS.has(catalogCitySlug))
                  ? (parsed.regionSlug ?? catalogCitySlug)
                  : undefined,
              city: isCityPrefix ? catalogCitySlug : parsed.citySlug,
              propertyType: parsed.propertyType,
              cityDistrict: value === "all" ? undefined : value,
            });
            router.push(url);
          }}
        >
          <SelectTrigger
            className={cn(
              filterSurfaceInput,
              "w-full cursor-pointer justify-between gap-2 text-left [&>span]:min-w-0 [&>span]:truncate",
            )}
          >
            <SelectValue placeholder="Любой район" />
          </SelectTrigger>
          <SelectContent className="bg-card border-border z-50 max-h-72">
            <SelectItem value="all">Любой район</SelectItem>
            {cityDistricts.map((district) => (
              <SelectItem key={district.id} value={district.slug}>
                {district.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      )}

      {showLandmarkSidebarFilters && cityLandmarks.length > 0 && (
      <div>
        <label className="text-sm font-semibold text-foreground mb-2 block font-display">Достопримечательность</label>
        <Select
          value="all"
          onValueChange={(value) => {
            const isCityPrefix = CITY_PREFIX_SLUGS.has(catalogCitySlug);
            const url = buildCatalogUrl({
              region:
                !isCityPrefix && (parsed.regionSlug || REGION_SLUGS.has(catalogCitySlug))
                  ? (parsed.regionSlug ?? catalogCitySlug)
                  : undefined,
              city: isCityPrefix ? catalogCitySlug : parsed.citySlug,
              propertyType: parsed.propertyType,
              landmark: value === "all" ? undefined : value,
            });
            router.push(url);
          }}
        >
          <SelectTrigger
            className={cn(
              filterSurfaceInput,
              "w-full cursor-pointer justify-between gap-2 text-left [&>span]:min-w-0 [&>span]:truncate",
            )}
          >
            <SelectValue placeholder="Любое место" />
          </SelectTrigger>
          <SelectContent className="bg-card border-border z-50 max-h-72">
            <SelectItem value="all">Любое место</SelectItem>
            {cityLandmarks.map((landmark) => (
              <SelectItem key={landmark.id} value={landmark.slug}>
                {landmark.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
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
  const showLandmarkBanner = isLandmarkPage && landmark && currentPage === 1;
  const breadcrumbSlot = children ? (
    <div className="container mx-auto min-w-0 px-4 pt-3 sm:pt-4 [&_nav]:mb-0">{children}</div>
  ) : null;

  const catalogHeading = (
    <>
      <h1 className="font-display font-bold text-2xl md:text-3xl text-foreground tracking-tight">
        {pageTitle}
      </h1>
      {nearMetroLanding && (
        <p className="text-muted-foreground text-sm md:text-base mt-2">
          {NEAR_METRO_CATALOG_INTRO}
        </p>
      )}
    </>
  );

  const landmarkResultsHeading = (
    <h2 className="font-display font-bold text-xl md:text-2xl text-foreground tracking-tight">
      Квартиры рядом
    </h2>
  );

  return (
    <div className="min-h-screen bg-background">
      {!showLandmarkBanner ? breadcrumbSlot : null}
      {showLandmarkBanner ? (
        <CatalogLandmarkBanner
          landmark={landmark}
          citySlug={catalogCitySlug}
          nearbyCount={isLoading ? null : catalogResultCount}
          isLoading={isLoading}
        />
      ) : null}
      {showLandmarkBanner ? breadcrumbSlot : null}

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
              {viewMode !== "map" && (
                <div>
                  <label className="text-sm font-semibold text-foreground mb-2 block font-display">Сортировка</label>
                  <Select value={sort} onValueChange={(value) => { setSort(value); resetToFirstPage(); }}>
                    <SelectTrigger className={cn(filterSurfaceInput, "w-full cursor-pointer")}>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-card border-border z-50">
                      {catalogSortOptions.map((o) => (
                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
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

      <section
        className={cn(
          "container mx-auto px-4",
          children ? "pt-3 pb-8 sm:pt-4" : "py-8",
          resultsBottomPadding,
        )}
      >
        {!isLandmarkPage ? <div className="mb-5">{catalogHeading}</div> : null}

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
            {isLandmarkPage ? <div className="mb-5">{landmarkResultsHeading}</div> : null}

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

                {viewMode !== "map" && (
                  <div className="hidden md:block shrink-0">
                    <Select value={sort} onValueChange={(value) => { setSort(value); resetToFirstPage(); }}>
                      <SelectTrigger className="h-auto min-h-0 cursor-pointer rounded-xl border-border bg-surface py-2.5 pl-3 pr-8 text-sm shadow-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-0">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-card border-border z-50">
                        {catalogSortOptions.map((o) => (
                          <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                )}

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
                    {[...Array(CATALOG_ITEMS_PER_PAGE)].map((_, i) => (
                      <div key={i} className="h-[360px] bg-muted/50 animate-pulse rounded-xl" />
                    ))}
                  </div>
                ) : displayProperties.length > 0 ? (
                  <>
                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                      {paginatedDisplayProperties.map((property, i) => {
                        const { primaryAmount, primaryCurrency, secondary } = formatPropertyPrices(property, exchangeRates, selectedCurrency);
                        const landmarkDistanceLabel =
                          isLandmarkPage && property.landmarkDistanceKm != null
                            ? `${formatLandmarkDistance(property.landmarkDistanceKm)} до объекта`
                            : undefined;
                        return (
                          <div key={property.id}>
                            <PropertyCard
                              id={property.id}
                              image={property.images?.[0]?.thumbnailUrl || property.images?.[0]?.url || "https://placehold.co/600x450?text=No+Image"}
                              price={<PriceDisplay amount={primaryAmount} currency={primaryCurrency} />}
                              primaryBynAmount={primaryAmount}
                              secondaryPrice={secondary}
                              title={property.title}
                              address={formatAddress(property.address, { includeCityDistrict: false })}
                              beds={property.specifications.rooms || 0}
                              baths={property.specifications.bathrooms ?? 1}
                              area={property.specifications.area || 0}
                              maxGuests={property.specifications.maxDailyGuests}
                              dealType={property.dealType}
                              propertyType={property.type}
                              regionSlug={propertyUrlRegionSlug(property.address.regionName, property.address.citySlug, property.type)}
                              typeLabel={property.typeLabel}
                              showTypeBadge={!parsed.propertyType}
                              index={i}
                              animateEntrance={false}
                              rating={property.ratingAvg ?? null}
                              reviewCount={property.reviewCount ?? null}
                              landmarkDistanceLabel={landmarkDistanceLabel}
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
                      {paginatedDisplayProperties.map((property, i) => {
                        const card = propertyToListCard(property, exchangeRates, metroFilterStationId, selectedCurrency);
                        const landmarkDistanceLabel =
                          isLandmarkPage && property.landmarkDistanceKm != null
                            ? `${formatLandmarkDistance(property.landmarkDistanceKm)} до объекта`
                            : undefined;
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
                              landmarkDistanceLabel={landmarkDistanceLabel}
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
            {currentPage === 1 && landmarkFooter ? (
              <CatalogLandmarkDetails
                landmark={landmarkFooter.landmark}
                citySlug={landmarkFooter.citySlug}
                relatedLandmarks={landmarkFooter.relatedLandmarks}
                descriptionHtml={landmarkFooter.descriptionHtml}
              />
            ) : null}
            {currentPage === 1 && citySeoFooter ? (
              <CatalogCitySeoSection heading={citySeoFooter.heading} html={citySeoFooter.html} />
            ) : null}
          </div>
        </div>
      </section>

    </div>
  );
}
