'use client';

import { useEffect, useMemo, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  ArrowLeft, Heart, Share2, MapPin, BedDouble, Bath, Maximize,
  Building2, Calendar, Layers, Phone, MessageCircle, Mail, TrainFront,
  ChevronLeft, ChevronRight, X, Shield, Eye, Clock, Send, CheckCircle
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useHasAuthToken } from "@/hooks/useHasAuthToken";
import { useProperty, useFavoriteIds, useToggleFavorite, useExchangeRates } from "@/features/properties/hooks";
import { trackPhoneView } from "@/features/properties/api";
import { useSendMessage } from "@/features/messages/hooks";
import { useUser } from "@/features/auth/hooks";
import { formatAddress, Property } from "@/features/properties/types";
import type { ExchangeRates } from "@/features/properties/api";
import { PriceInByn } from "@/components/BynCurrency";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import PropertyMap from "@/components/PropertyMap";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import { toast } from "sonner";
import {
  HEADER_CITY_SLUG_SET,
  LISTING_REGION_SESSION_KEY,
  REGION_SYNC_EVENT,
  regionNameToHeaderSlug,
} from "@/lib/region-header";
import {
  showBalcony,
  showBathrooms,
  showDealConditions,
  showFloor,
  showKitchenArea,
  showLivingArea,
  showRenovation,
  showRoomDealFields,
  showRooms,
  showTotalFloors,
  showYearBuilt,
} from "@/features/create-listing/property-field-rules";
import { formatPropertyDealHeading } from "@/features/properties/property-deal-heading";

type PropertyDetailClientProps = {
  id: number;
  initialProperty: Property;
};

const PROPERTY_TYPE_LABELS: Record<string, string> = {
  apartment: "Квартира",
  house: "Дом",
  room: "Комната",
  land: "Участок",
  garage: "Гараж",
  parking: "Машиноместо",
  dacha: "Дача",
  office: "Офис",
  retail: "Торговое помещение",
  warehouse: "Склад",
  business: "Готовый бизнес",
};

function initialsFromContactName(name: string): string {
  const trimmed = name.trim();
  if (!trimmed) return "?";
  const words = trimmed.split(/\s+/).filter(Boolean);
  if (words.length >= 2) {
    const first = words[0][0];
    const last = words[words.length - 1][0];
    return `${first}${last}`.toUpperCase();
  }
  return trimmed.slice(0, Math.min(2, trimmed.length)).toUpperCase();
}

/** Preview line before reveal: +375 XX ***-**-67 from stored phone digits. */
function maskContactPhone(phone: string): string {
  const digits = phone.replace(/\D/g, "");
  if (digits.length < 2) return "Телефон";
  const last2 = digits.slice(-2);
  if (digits.startsWith("375") && digits.length >= 11) {
    const op = digits.slice(3, 5);
    return `+375 ${op} ***-**-${last2}`;
  }
  if (digits.startsWith("80") && digits.length >= 11) {
    const op = digits.slice(2, 4);
    return `+375 ${op} ***-**-${last2}`;
  }
  if (digits.length <= 4) {
    return `***${last2}`;
  }
  return `***-**-${last2}`;
}

/** Build tel: href for dialing; aligns 80… Belarus numbers with +375 like maskContactPhone. */
function phoneToTelHref(phone: string): string {
  const digits = phone.replace(/\D/g, "");
  if (!digits) return "#";
  let d = digits;
  if (d.startsWith("80") && d.length >= 11) {
    d = `375${d.slice(2)}`;
  }
  return `tel:+${d}`;
}

export default function PropertyDetailClient({ id, initialProperty }: PropertyDetailClientProps) {
  const { data: property, isLoading, isError } = useProperty(id, { initialData: initialProperty });
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const priceDisplay = useMemo(() => {
    const p = property ?? initialProperty;
    return formatPropertyPrices(p, exchangeRates);
  }, [property, initialProperty, exchangeRates]);

  const { data: currentUser } = useUser();
  const pathname = usePathname();
  const loggedIn = useHasAuthToken();
  const loginWithReturnHref = `/login?next=${encodeURIComponent(pathname)}`;

  const { data: favoriteIds = [] } = useFavoriteIds();
  const { mutate: toggleFavorite } = useToggleFavorite();
  const isFavorited = favoriteIds.includes(id);

  const [currentImage, setCurrentImage] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const touchStartX = useRef<number | null>(null);
  const swipeHandled = useRef(false);
  const [phoneRevealed, setPhoneRevealed] = useState(false);
  const [messageOpen, setMessageOpen] = useState(false);
  const [messageText, setMessageText] = useState("");
  const [messageSent, setMessageSent] = useState(false);
  const sendMessageMutation = useSendMessage();

  useEffect(() => {
    if (!property) return;
    const slug = regionNameToHeaderSlug(property.address.regionName);
    if (!slug || !HEADER_CITY_SLUG_SET.has(slug)) return;

    try {
      sessionStorage.setItem(LISTING_REGION_SESSION_KEY, slug);
    } catch {
      /* sessionStorage unavailable */
    }
    window.dispatchEvent(new Event(REGION_SYNC_EVENT));

    return () => {
      try {
        sessionStorage.removeItem(LISTING_REGION_SESSION_KEY);
      } catch {
        /* storage unavailable */
      }
      window.dispatchEvent(new Event(REGION_SYNC_EVENT));
    };
  }, [property]);

  if (isLoading) {
    return (
      <div className="min-h-screen bg-background pt-16">
        <div className="container mx-auto px-4 py-8 animate-pulse">
          <div className="h-6 w-40 bg-muted rounded mb-8" />
          <div className="grid grid-cols-1 md:grid-cols-4 gap-2 rounded-2xl overflow-hidden max-h-[500px] mb-8">
            <div className="md:col-span-2 md:row-span-2 h-[300px] bg-muted" />
            <div className="h-[148px] bg-muted hidden md:block" />
            <div className="h-[148px] bg-muted hidden md:block" />
            <div className="h-[148px] bg-muted hidden md:block" />
            <div className="h-[148px] bg-muted hidden md:block" />
          </div>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2 space-y-6">
              <div className="h-8 w-3/4 bg-muted rounded" />
              <div className="h-4 w-1/2 bg-muted rounded" />
              <div className="grid grid-cols-3 sm:grid-cols-6 gap-3">
                {[...Array(6)].map((_, i) => <div key={i} className="h-20 bg-muted rounded-xl" />)}
              </div>
            </div>
            <div className="h-96 bg-muted rounded-2xl" />
          </div>
        </div>
      </div>
    );
  }

  if (isError || !property) {
    return (
      <div className="min-h-screen bg-background pt-16">
        <div className="container mx-auto px-4 py-20 text-center">
          <MapPin className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
          <h1 className="text-2xl font-bold mb-4">Объект не найден</h1>
          <p className="text-muted-foreground mb-8">
            Объект, который вы ищете, не существует или был удалён.
          </p>
          <Button asChild>
            <Link href="/kvartiry/">Вернуться в каталог</Link>
          </Button>
        </div>
      </div>
    );
  }

  const isOwner = currentUser?.id != null && currentUser.id === property.ownerId;

  const sellerName = property.contact?.name?.trim() || "Продавец";
  const sellerInitials = property.contact?.name?.trim()
    ? initialsFromContactName(property.contact.name)
    : "?";
  const contactPhone = property.contact?.phone?.trim() ?? "";

  const images = property.images?.map(img => img.url) || [
    "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80",
  ];
  const formatStableDate = (value: string) => {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "-";

    const day = String(date.getUTCDate()).padStart(2, "0");
    const month = String(date.getUTCMonth() + 1).padStart(2, "0");
    const year = date.getUTCFullYear();

    return `${day}.${month}.${year}`;
  };
  const addressStr = formatAddress(property.address);
  const backToCatalogHref = buildCatalogUrl({
    region: regionNameToHeaderSlug(property.address.regionName),
    propertyType: property.type,
  }) || "/kvartiry/";
  const coords = property.coordinates;
  const nearbyMetroStations = property.nearbyMetroStations ?? [];

  const lineColorClass = (line: number): string => {
    if (line === 1) return "bg-[#E3000B]";
    if (line === 2) return "bg-[#006DB7]";
    if (line === 3) return "bg-[#007A33]";
    return "bg-muted-foreground";
  };

  const formatDistance = (distanceKm: number): string => {
    if (distanceKm < 1) {
      const meters = Math.ceil((distanceKm * 1000) / 100) * 100;
      return `~${meters} м`;
    }

    return `~${distanceKm.toFixed(1)} км`;
  };

  const specs = [
    property.type === "land"
      ? {
          icon: Maximize,
          label: "Площадь участка",
          value: property.specifications.landArea ? `${property.specifications.landArea} сот.` : "-",
        }
      : { icon: Maximize, label: "Площадь общая", value: `${property.specifications.area} м²` },
    ...(property.type === "house" || property.type === "dacha"
      ? [
          {
            icon: MapPin,
            label: "Площадь участка",
            value: property.specifications.landArea ? `${property.specifications.landArea} сот.` : "-",
          },
        ]
      : []),
    ...(showRooms(property.type) && property.specifications.rooms != null
      ? [{ icon: BedDouble, label: "Комнаты", value: String(property.specifications.rooms) }]
      : []),
    ...(showRoomDealFields(property.type, property.dealType) && property.specifications.roomsInDeal != null
      ? [{ icon: BedDouble, label: "Комнат в сделке", value: String(property.specifications.roomsInDeal) }]
      : []),
    ...(showRoomDealFields(property.type, property.dealType) && property.specifications.roomsArea != null
      ? [{ icon: Maximize, label: "Площадь комнат в сделке", value: `${property.specifications.roomsArea} м²` }]
      : []),
    ...(showBathrooms(property.type) && property.specifications.bathrooms != null
      ? [{ icon: Bath, label: "Санузлы", value: String(property.specifications.bathrooms) }]
      : []),
    ...(showFloor(property.type) && property.specifications.floor != null
      ? [{
          icon: Layers,
          label: "Этаж",
          value: property.specifications.totalFloors != null
            ? `${property.specifications.floor} из ${property.specifications.totalFloors}`
            : String(property.specifications.floor),
        }]
      : showTotalFloors(property.type) && property.specifications.totalFloors != null
      ? [{ icon: Layers, label: "Этажей", value: String(property.specifications.totalFloors) }]
      : []),
    ...(showYearBuilt(property.type) && property.specifications.yearBuilt != null
      ? [{ icon: Calendar, label: "Год постройки", value: String(property.specifications.yearBuilt) }]
      : []),
    ...(showRenovation(property.type) && property.specifications.renovation
      ? [{ icon: CheckCircle, label: "Ремонт", value: property.specifications.renovation }]
      : []),
    ...(showBalcony(property.type) && property.specifications.balcony && property.specifications.balcony !== "Нет"
      ? [{ icon: CheckCircle, label: "Балкон", value: property.specifications.balcony }]
      : []),
    ...(showLivingArea(property.type) && property.specifications.livingArea != null
      ? [{ icon: Maximize, label: "Жилая", value: `${property.specifications.livingArea} м²` }]
      : []),
    ...(showKitchenArea(property.type) && property.specifications.kitchenArea != null
      ? [{ icon: Maximize, label: "Кухня", value: `${property.specifications.kitchenArea} м²` }]
      : []),
    ...(showDealConditions(property.dealType) && (property.specifications.dealConditions?.length ?? 0) > 0
      ? [{ icon: CheckCircle, label: "Условия", value: property.specifications.dealConditions!.join(", ") }]
      : []),
    ...(property.dealType === "daily" && property.specifications.maxDailyGuests != null
      ? [{ icon: BedDouble, label: "Гостей", value: String(property.specifications.maxDailyGuests) }]
      : []),
    ...(property.dealType === "daily" && property.specifications.dailySingleBeds != null
      ? [{ icon: BedDouble, label: "Односпальных кроватей", value: String(property.specifications.dailySingleBeds) }]
      : []),
    ...(property.dealType === "daily" && property.specifications.dailyDoubleBeds != null
      ? [{ icon: BedDouble, label: "Двуспальных кроватей", value: String(property.specifications.dailyDoubleBeds) }]
      : []),
    ...(property.dealType === "daily" && property.specifications.checkInTime
      ? [{ icon: Clock, label: "Заезд", value: property.specifications.checkInTime }]
      : []),
    ...(property.dealType === "daily" && property.specifications.checkOutTime
      ? [{ icon: Clock, label: "Выезд", value: property.specifications.checkOutTime }]
      : []),
    {
      icon: Building2,
      label: "Тип",
      value: property.typeLabel ?? PROPERTY_TYPE_LABELS[property.type] ?? property.type,
    },
  ].filter((spec) => spec.value !== "-");

  const prevImage = () => setCurrentImage((p) => (p === 0 ? images.length - 1 : p - 1));
  const nextImage = () => setCurrentImage((p) => (p === images.length - 1 ? 0 : p + 1));
  const handleGalleryTouchStart = (event: React.TouchEvent<HTMLDivElement>) => {
    if (images.length <= 1) return;
    touchStartX.current = event.touches[0]?.clientX ?? null;
    swipeHandled.current = false;
  };
  const handleGalleryTouchEnd = (event: React.TouchEvent<HTMLDivElement>) => {
    if (images.length <= 1 || touchStartX.current == null) return;

    const touchEndX = event.changedTouches[0]?.clientX;
    if (touchEndX == null) return;

    const deltaX = touchStartX.current - touchEndX;
    const SWIPE_THRESHOLD = 40;

    if (Math.abs(deltaX) > SWIPE_THRESHOLD) {
      swipeHandled.current = true;
      if (deltaX > 0) {
        nextImage();
      } else {
        prevImage();
      }
    }

    touchStartX.current = null;
  };
  const handleShare = async () => {
    const shareUrl = window.location.href;
    const shareData = {
      title: property.title,
      text: `${property.title} — ${priceDisplay.primaryPlain}`,
      url: shareUrl,
    };

    try {
      if (navigator.share) {
        await navigator.share(shareData);
        return;
      }

      await navigator.clipboard.writeText(shareUrl);
      toast.success("Ссылка скопирована");
    } catch {
      try {
        await navigator.clipboard.writeText(shareUrl);
        toast.success("Ссылка скопирована");
      } catch {
        toast.error("Не удалось поделиться. Скопируйте ссылку из адресной строки.");
      }
    }
  };

  return (
    <div className="min-h-screen bg-background">
      <main className="pt-16">
        <div className="container mx-auto px-4 py-4">
          <Link href={backToCatalogHref} className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors">
            <ArrowLeft className="w-4 h-4" />
            Назад к объявлениям
          </Link>
        </div>

        <section className="container mx-auto px-4 mb-8">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-2 rounded-2xl overflow-hidden max-h-[500px]">
            <motion.div
              className="md:col-span-2 md:row-span-2 relative cursor-pointer group aspect-[4/3] w-full min-h-0 overflow-hidden md:aspect-auto md:h-full"
              onClick={() => {
                if (swipeHandled.current) {
                  swipeHandled.current = false;
                  return;
                }
                setLightboxOpen(true);
              }}
              onTouchStart={handleGalleryTouchStart}
              onTouchEnd={handleGalleryTouchEnd}
              whileHover={{ scale: 1.005 }}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={images[0]} alt="Главное фото" className="hidden md:block w-full h-full object-cover min-h-[300px]" />
              {/* Mobile: sharp center + blur only in side columns (portrait letterbox) */}
              <div className="md:hidden absolute inset-0 flex min-h-0 min-w-0">
                <div className="relative min-h-0 min-w-0 flex-1 overflow-hidden">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={images[currentImage]}
                    alt=""
                    aria-hidden
                    className="pointer-events-none absolute inset-0 h-full w-full object-cover object-left blur-sm"
                  />
                </div>
                <div className="relative z-[1] flex h-full shrink-0 items-center justify-center">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={images[currentImage]}
                    alt={`Фото ${currentImage + 1}`}
                    className="max-h-full w-auto max-w-full object-contain"
                  />
                </div>
                <div className="relative min-h-0 min-w-0 flex-1 overflow-hidden">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={images[currentImage]}
                    alt=""
                    aria-hidden
                    className="pointer-events-none absolute inset-0 h-full w-full object-cover object-right blur-sm"
                  />
                </div>
              </div>
              <div className="absolute inset-0 z-[2] bg-foreground/0 group-hover:bg-foreground/10 transition-colors" />
            </motion.div>
            {images.slice(1, 5).map((img, i) => (
              <div
                key={i}
                className="relative cursor-pointer group hidden md:block"
                onClick={() => { setCurrentImage(i + 1); setLightboxOpen(true); }}
              >
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={img} alt={`Фото ${i + 2}`} className="w-full h-full object-cover aspect-[4/3]" />
                <div className="absolute inset-0 bg-foreground/0 group-hover:bg-foreground/10 transition-colors" />
                {i === 3 && images.length > 5 && (
                  <div className="absolute inset-0 bg-foreground/50 flex items-center justify-center">
                    <span className="text-primary-foreground font-medium text-sm">+{images.length - 4} фото</span>
                  </div>
                )}
              </div>
            ))}
          </div>
          <div className="md:hidden flex items-center justify-center gap-2 mt-3">
            <button
              type="button"
              aria-label="Предыдущее фото"
              onClick={prevImage}
              className="p-1.5 rounded-full bg-muted touch-manipulation transition-[transform,background-color,color] duration-150 ease-out active:scale-95 active:bg-primary/25 active:text-primary"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
            <span className="text-sm text-muted-foreground">{currentImage + 1} / {images.length}</span>
            <button
              type="button"
              aria-label="Следующее фото"
              onClick={nextImage}
              className="p-1.5 rounded-full bg-muted touch-manipulation transition-[transform,background-color,color] duration-150 ease-out active:scale-95 active:bg-primary/25 active:text-primary"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
          {images.length > 1 && (
            <div className="md:hidden mt-3 flex items-center justify-center gap-1.5">
              {images.map((_, index) => (
                <button
                  key={index}
                  type="button"
                  onClick={() => setCurrentImage(index)}
                  aria-label={`Перейти к фото ${index + 1}`}
                  className={`h-2 rounded-full transition-all ${
                    currentImage === index
                      ? "w-5 bg-primary"
                      : "w-2 bg-muted-foreground/40 hover:bg-muted-foreground/60"
                  }`}
                />
              ))}
            </div>
          )}
        </section>

        <div className="container mx-auto px-4 pb-16">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2 space-y-8">
              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
                <div className="flex items-start justify-between gap-4 mb-3">
                  <div>
                    <span className="inline-block px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium mb-3">
                      {formatPropertyDealHeading(property.dealType, property.type)}
                    </span>
                    <h1 className="text-2xl md:text-3xl font-bold text-foreground">{property.title}</h1>
                  </div>
                  <div className="flex gap-2 shrink-0">
                    <button
                      type="button"
                      onClick={() => {
                        if (!currentUser) {
                          window.location.href = '/login';
                          return;
                        }
                        toggleFavorite({ propertyId: id, isFavorited });
                      }}
                      className={`p-2.5 rounded-xl transition-colors ${
                        isFavorited
                          ? 'bg-primary/10 hover:bg-primary/20'
                          : 'bg-muted hover:bg-muted/80'
                      }`}
                    >
                      <Heart className={`w-5 h-5 ${isFavorited ? 'fill-primary text-primary' : 'text-muted-foreground'}`} />
                    </button>
                    <button
                      type="button"
                      onClick={() => { void handleShare(); }}
                      className="p-2.5 rounded-xl bg-muted hover:bg-muted/80 transition-colors"
                    >
                      <Share2 className="w-5 h-5 text-muted-foreground" />
                    </button>
                  </div>
                </div>
                <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:gap-3">
                  <p className="flex items-center gap-1.5 text-muted-foreground min-w-0">
                    <MapPin className="w-4 h-4 shrink-0" />
                    <span className="truncate">{addressStr}</span>
                  </p>
                  {nearbyMetroStations.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2 md:flex-nowrap">
                      {nearbyMetroStations.slice(0, 2).map((station) => {
                        const stationHref = buildCatalogUrl({
                          propertyType: property.type,
                          nearMetro: true,
                          metroStation: station.slug,
                        });

                        const content = (
                          <>
                            <TrainFront className="h-3 w-3 text-muted-foreground" />
                            <span className={`h-2 w-2 rounded-full ${lineColorClass(station.line)}`} />
                            {station.name}
                            <span className="text-muted-foreground">{formatDistance(station.distanceKm)}</span>
                          </>
                        );

                        return (
                          <Link
                            key={station.id}
                            href={stationHref}
                            className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs text-foreground/85 hover:bg-muted/80 transition-colors"
                          >
                            {content}
                          </Link>
                        );
                      })}
                    </div>
                  )}
                </div>
                <div className="flex items-baseline gap-3 flex-wrap">
                  {/* Solid color: text-gradient-primary uses -webkit-text-fill-color: transparent and hides nested nbrb glyph */}
                  <span className="text-3xl font-bold text-primary">
                    <PriceInByn amount={priceDisplay.primaryAmount} />
                  </span>
                  <span className="text-sm text-muted-foreground">{priceDisplay.secondary}</span>
                </div>
              </motion.div>

              <motion.div
                initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1, duration: 0.5 }}
                className="grid grid-cols-3 sm:grid-cols-6 gap-3"
              >
                {specs.map((spec) => (
                  <div key={spec.label} className="bg-muted rounded-xl p-3 text-center">
                    <spec.icon className="w-5 h-5 mx-auto text-primary mb-1.5" />
                    <p className="text-xs text-muted-foreground mb-0.5">{spec.label}</p>
                    <p className="text-sm font-semibold text-foreground">{spec.value}</p>
                  </div>
                ))}
              </motion.div>

              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2, duration: 0.5 }}>
                <h2 className="text-xl font-bold text-foreground mb-4">Описание</h2>
                <div className="text-muted-foreground leading-relaxed space-y-3 whitespace-pre-line">
                  {property.description}
                </div>
              </motion.div>

              {coords?.latitude && coords?.longitude && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-4">Расположение</h2>
                  <div className="rounded-2xl overflow-hidden border border-border h-[350px]">
                    <PropertyMap
                      properties={[{
                        id: property.id,
                        lat: coords.latitude,
                        lng: coords.longitude,
                        title: property.title,
                        price: priceDisplay.primaryPlain,
                        address: addressStr,
                        image: images[0],
                        dealType: property.dealType,
                        propertyType: property.type,
                      }]}
                      showBalloons={false}
                    />
                  </div>
                </motion.div>
              )}
            </div>

            <div className="space-y-4">
              <motion.div
                initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.2, duration: 0.5 }}
                className="bg-card rounded-2xl p-6 shadow-card sticky top-20"
              >
                <div className="flex items-center gap-3 mb-5">
                  <div className="w-14 h-14 rounded-xl bg-gradient-primary flex items-center justify-center text-primary-foreground font-bold text-lg">
                    {sellerInitials}
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Контакт:</p>
                    <p className="font-semibold text-foreground">{sellerName}</p>
                  </div>
                </div>

                <div className="space-y-2.5 mb-5">
                  {contactPhone && phoneRevealed ? (
                    <Button
                      className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0 h-11"
                      asChild
                    >
                      <a href={phoneToTelHref(contactPhone)}>
                        <Phone className="w-4 h-4 mr-2" />
                        {contactPhone}
                      </a>
                    </Button>
                  ) : (
                    <Button
                      className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0 h-11"
                      disabled={!contactPhone}
                      onClick={() => {
                        if (!contactPhone) return;
                        void trackPhoneView(property.id);
                        setPhoneRevealed(true);
                      }}
                    >
                      <Phone className="w-4 h-4 mr-2" />
                      {!contactPhone
                        ? "Телефон не указан"
                        : `${maskContactPhone(contactPhone)} · Показать`}
                    </Button>
                  )}
                  {!isOwner && loggedIn && (
                    <Button
                      variant="outline"
                      className="w-full h-11"
                      onClick={() => { setMessageOpen(true); setMessageSent(false); }}
                    >
                      <MessageCircle className="w-4 h-4 mr-2" />
                      Написать сообщение
                    </Button>
                  )}
                  {!isOwner && !loggedIn && (
                    <Button variant="outline" className="w-full h-11" asChild>
                      <Link href={loginWithReturnHref}>
                        <MessageCircle className="w-4 h-4 mr-2" />
                        Войти, чтобы написать
                      </Link>
                    </Button>
                  )}
                  {!isOwner && (
                    <Button
                      variant="outline"
                      className="w-full h-11"
                      asChild
                    >
                      <a href={`mailto:example@mail.ru?subject=Запрос по объявлению: ${property.title}&body=Здравствуйте! Меня интересует ваш объект: ${property.title} (ID: ${property.id}). Прошу связаться со мной для уточнения деталей.`}>
                        <Mail className="w-4 h-4 mr-2" />
                        Отправить на почту
                      </a>
                    </Button>
                  )}
                </div>

                <AnimatePresence>
                  {!isOwner && loggedIn && messageOpen && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: "auto", opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      className="overflow-hidden mb-5"
                    >
                      {messageSent ? (
                        <div className="flex flex-col items-center gap-2 py-4 text-center">
                          <CheckCircle className="w-8 h-8 text-primary" />
                          <p className="text-sm font-medium text-foreground">Сообщение отправлено!</p>
                          <p className="text-xs text-muted-foreground">Продавец получит уведомление</p>
                          <Button variant="ghost" size="sm" onClick={() => setMessageOpen(false)} className="mt-1 text-xs">Закрыть</Button>
                        </div>
                      ) : (
                        <div className="space-y-3 pt-3 border-t border-border">
                          <p className="text-sm font-medium text-foreground">Сообщение продавцу</p>
                          <Textarea
                            placeholder="Например: Здравствуйте! Интересует ваш объект..."
                            value={messageText}
                            onChange={(e) => setMessageText(e.target.value)}
                            className="min-h-[80px] resize-none text-sm border border-border ring-0 ring-offset-0 focus-visible:ring-1 focus-visible:ring-primary focus-visible:ring-offset-0"
                            maxLength={1000}
                          />
                          <div className="flex items-center gap-2">
                            <Button
                              className="flex-1 bg-gradient-primary text-primary-foreground border-0 h-9"
                              disabled={!messageText.trim() || sendMessageMutation.isPending}
                              onClick={() => {
                                sendMessageMutation.mutate(
                                  { text: messageText, propertyId: id },
                                  {
                                    onSuccess: () => { setMessageSent(true); setMessageText(""); },
                                  }
                                );
                              }}
                            >
                              <Send className="w-3.5 h-3.5 mr-1.5" />
                              Отправить
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => setMessageOpen(false)} className="h-9">
                              Отмена
                            </Button>
                          </div>
                        </div>
                      )}
                    </motion.div>
                  )}
                </AnimatePresence>

                <div className="pt-4 border-t border-border space-y-2">
                  <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Eye className="w-4 h-4" />
                    <span>Просмотры: {property.views ?? 0}</span>
                  </div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Clock className="w-4 h-4" />
                    <span>Опубликовано {formatStableDate(property.createdAt)}</span>
                  </div>
                  <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Calendar className="w-4 h-4" />
                    <span>ID: {property.id}</span>
                  </div>
                </div>
              </motion.div>

              <div className="bg-accent rounded-2xl p-4">
                <div className="flex items-start gap-2">
                  <Shield className="w-5 h-5 text-primary mt-0.5 shrink-0" />
                  <div>
                    <p className="text-sm font-medium text-accent-foreground">Совет безопасности</p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Никогда не переводите предоплату без осмотра объекта и проверки документов. Встречайтесь в общественных местах.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      <AnimatePresence>
        {lightboxOpen && (
          <motion.div
            initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
            className="fixed inset-0 z-[100] bg-foreground/95 flex items-center justify-center"
            onClick={() => setLightboxOpen(false)}
          >
            <button
              type="button"
              aria-label="Закрыть просмотр"
              onClick={() => setLightboxOpen(false)}
              className="absolute top-4 right-4 p-2 text-background/70 transition-[opacity,transform,color] duration-150 hover:text-background active:scale-95 active:text-primary active:opacity-100"
            >
              <X className="w-6 h-6" />
            </button>
            <button
              type="button"
              aria-label="Предыдущее фото"
              onClick={(e) => { e.stopPropagation(); prevImage(); }}
              className="absolute left-4 touch-manipulation rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
            >
              <ChevronLeft className="w-6 h-6" />
            </button>
            <motion.img
              key={currentImage}
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              src={images[currentImage]}
              alt=""
              className="max-h-[85vh] max-w-[90vw] object-contain rounded-lg"
              onClick={(e) => e.stopPropagation()}
            />
            <button
              type="button"
              aria-label="Следующее фото"
              onClick={(e) => { e.stopPropagation(); nextImage(); }}
              className="absolute right-4 touch-manipulation rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
            >
              <ChevronRight className="w-6 h-6" />
            </button>
            <div className="absolute bottom-6 text-background/70 text-sm">
              {currentImage + 1} / {images.length}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
