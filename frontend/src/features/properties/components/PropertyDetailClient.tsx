'use client';

import { useEffect, useMemo, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  ArrowLeft, Heart, Share2, MapPin, BedDouble, Bath, Maximize,
  Building2, Calendar, CalendarCheck, Layers, Phone, MessageCircle, TrainFront,
  ChevronLeft, ChevronRight, X, Shield, Eye, Clock, Send, CheckCircle,
  Users, Utensils, Wifi, Tv, Sofa, Car, Waves, Wind,
  ShowerHead, Flame, Coffee, Snowflake, Baby, WashingMachine,
  LogIn, LogOut, UserCheck, Sunrise, Wallet,
} from "lucide-react";
import { LISTING_AMENITY_GROUPS } from "@/features/create-listing/listing-amenity-groups";
import { PAYMENT_METHOD_LABELS, type PaymentMethodId } from "@/features/properties/payment-methods";
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
import { formatPropertyDealHeading } from "@/features/properties/property-deal-heading";
import type { ExchangeRates } from "@/features/properties/api";
import { PriceDisplay } from "@/components/BynCurrency";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import { useCurrency } from "@/context/CurrencyContext";
import PropertyMap from "@/components/PropertyMap";
import { BookingInquiryModal } from "@/features/properties/components/BookingInquiryModal";
import { buildCatalogUrl, buildCatalogUrlFromAddress } from "@/features/catalog/slugs";
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
import { ReviewForm } from "@/features/reviews/components/ReviewForm";
import { ReviewList } from "@/features/reviews/components/ReviewList";
import { ReviewSummary } from "@/features/reviews/components/ReviewSummary";
import { useDeletePendingReview, usePropertyReviews } from "@/features/reviews/hooks";
import { telegramHref, viberChatHref, whatsAppHref } from "@/lib/contactLinks";
import { TelegramIcon, ViberIcon, WhatsAppIcon } from "@/components/ContactMessengerIcons";

type PropertyDetailClientProps = {
  id: number;
  initialProperty: Property;
};

const PROPERTY_TYPE_LABELS: Record<string, string> = {
  apartment: "Квартира",
  house: "Дом",
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

type ContactPhoneEntry = {
  phone: string;
  hasViber: boolean;
  hasWhatsapp: boolean;
};

function getContactPhones(property: Property): ContactPhoneEntry[] {
  const fromApi = property.contact?.phones;
  if (fromApi && fromApi.length > 0) {
    return fromApi
      .map((p) => ({
        phone: p.phone?.trim() ?? "",
        hasViber: !!p.hasViber,
        hasWhatsapp: !!p.hasWhatsapp,
      }))
      .filter((p) => p.phone !== "");
  }
  const legacy = property.contact?.phone?.trim();
  if (legacy) {
    return [{ phone: legacy, hasViber: false, hasWhatsapp: false }];
  }
  return [];
}

/** Side-column letterbox with blurred edges. Parent must be `relative` with explicit size. */
function GalleryPortraitFrame({
  src,
  alt,
  className = "absolute inset-0 flex min-h-0 min-w-0",
}: {
  src: string;
  alt: string;
  className?: string;
}) {
  return (
    <div className={className}>
      <div className="relative min-h-0 min-w-0 flex-1 overflow-hidden">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={src}
          alt=""
          aria-hidden
          className="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover object-left blur-md"
        />
      </div>
      <div className="relative z-[1] flex h-full shrink-0 items-center justify-center">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={src} alt={alt} className="max-h-full w-auto max-w-full object-contain" />
      </div>
      <div className="relative min-h-0 min-w-0 flex-1 overflow-hidden">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={src}
          alt=""
          aria-hidden
          className="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover object-right blur-md"
        />
      </div>
    </div>
  );
}

/** True only when image is clearly wider than tall. */
function useImageClearlyLandscape(src: string): boolean | null {
  const [clearlyLandscape, setClearlyLandscape] = useState<boolean | null>(null);

  useEffect(() => {
    setClearlyLandscape(null);
    const img = new Image();
    const finish = () => {
      if (img.naturalWidth > 0 && img.naturalHeight > 0) {
        setClearlyLandscape(img.naturalWidth > img.naturalHeight * 1.02);
      }
    };
    img.addEventListener("load", finish);
    img.addEventListener("error", () => setClearlyLandscape(false));
    img.src = src;
    if (img.complete) finish();
    return () => {
      img.removeEventListener("load", finish);
    };
  }, [src]);

  return clearlyLandscape;
}

function GalleryGridThumb({ src, alt }: { src: string; alt: string }) {
  const clearlyLandscape = useImageClearlyLandscape(src);

  return (
    <div className="relative aspect-[4/3] w-full overflow-hidden">
      {clearlyLandscape === true ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={src} alt={alt} className="absolute inset-0 h-full w-full object-cover" />
      ) : (
        <GalleryPortraitFrame src={src} alt={alt} />
      )}
    </div>
  );
}

export default function PropertyDetailClient({ id, initialProperty }: PropertyDetailClientProps) {
  const { data: property, isLoading, isError } = useProperty(id, { initialData: initialProperty });
  const { selectedCurrency } = useCurrency();
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const priceDisplay = useMemo(() => {
    const p = property ?? initialProperty;
    return formatPropertyPrices(p, exchangeRates, selectedCurrency);
  }, [property, initialProperty, exchangeRates, selectedCurrency]);

  const { data: currentUser } = useUser();
  const pathname = usePathname();
  const loggedIn = useHasAuthToken() || !!currentUser;
  const loginWithReturnHref = `/login?next=${encodeURIComponent(pathname)}`;

  const { data: favoriteIds = [] } = useFavoriteIds();
  const { mutate: toggleFavorite } = useToggleFavorite();
  const isFavorited = favoriteIds.includes(id);

  const { data: reviewsData, isLoading: reviewsLoading } = usePropertyReviews(id);
  const deleteReviewMutation = useDeletePendingReview(id);

  const [currentImage, setCurrentImage] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const touchStartX = useRef<number | null>(null);
  const swipeHandled = useRef(false);
  const [phoneRevealed, setPhoneRevealed] = useState(false);
  const [messageOpen, setMessageOpen] = useState(false);
  const [messageText, setMessageText] = useState("");
  const [messageSent, setMessageSent] = useState(false);
  const [bookingOpen, setBookingOpen] = useState(false);
  const sendMessageMutation = useSendMessage();

  const mainImageSrc =
    property?.images?.[0]?.url ??
    initialProperty.images?.[0]?.url ??
    "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80";
  const mainImageClearlyLandscape = useImageClearlyLandscape(mainImageSrc);

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
      <div className="min-h-screen bg-background">
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
      <div className="min-h-screen bg-background">
        <div className="container mx-auto px-4 py-20 text-center">
          <MapPin className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
          <h1 className="text-2xl font-bold mb-4">Объект не найден</h1>
          <p className="text-muted-foreground mb-8">
            Объект, который вы ищете, не существует или был удалён.
          </p>
          <Button asChild>
            <Link
              href={buildCatalogUrlFromAddress(
                initialProperty.address.regionName,
                initialProperty.address.citySlug,
                initialProperty.type,
              )}
            >
              Вернуться в каталог
            </Link>
          </Button>
        </div>
      </div>
    );
  }

  const isOwner =
    currentUser?.id != null &&
    property.ownerId != null &&
    Number(currentUser.id) === Number(property.ownerId);

  const viewerReview = property.viewerReview;
  const canLeaveReview =
    property.status === "published" && !isOwner && loggedIn && (!viewerReview || viewerReview.status === "rejected");
  const hasPendingOwnReview = viewerReview?.status === "pending";
  const hasApprovedOwnReview = viewerReview?.status === "approved";

  const sellerName = property.contact?.name?.trim() || "Продавец";
  const sellerInitials = property.contact?.name?.trim()
    ? initialsFromContactName(property.contact.name)
    : "?";
  const contactPhones = getContactPhones(property);
  const primaryContactPhone = contactPhones[0]?.phone ?? "";
  const hasContactPhones = contactPhones.length > 0;
  const contactTelegram = property.contact?.telegram?.trim() ?? "";
  const canBookInquiry = property.contact?.hasEmail === true;

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
  const backToCatalogHref = buildCatalogUrlFromAddress(
    property.address.regionName,
    property.address.citySlug,
    property.type,
  );
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

  const AMENITY_ICON_MAP: Record<string, React.ElementType> = {
    fridge: Utensils,
    electric_stove: Flame,
    gas_stove: Flame,
    induction_stove: Flame,
    oven: Utensils,
    microwave: Utensils,
    dishwasher: Utensils,
    coffee_machine: Coffee,
    kettle: Coffee,
    blender: Utensils,
    dishes_utensils: Utensils,
    bathroom_separate: ShowerHead,
    bathroom_combined: ShowerHead,
    jacuzzi: Waves,
    rain_shower: ShowerHead,
    towels: Bath,
    hairdryer: Wind,
    bathrobes: Bath,
    toiletries: Bath,
    smart_tv: Tv,
    tv: Tv,
    wifi: Wifi,
    playstation: Tv,
    bluetooth_speaker: Tv,
    projector: Tv,
    cable_tv: Tv,
    air_conditioner: Snowflake,
    heated_floor: Flame,
    iron: Wind,
    washing_machine: WashingMachine,
    dryer: WashingMachine,
    robot_vacuum: Sofa,
    crib: Baby,
    high_chair: Baby,
    parking_open: Car,
    parking_covered: Car,
    cctv: Shield,
    gazebo: Sofa,
    pool: Waves,
    pond: Waves,
    bbq: Flame,
    sauna: Waves,
    playground: Baby,
    garden: Sofa,
    furniture: Sofa,
    appliances: Utensils,
  };

  const DEAL_CONDITION_LABELS: Record<string, string> = {
    contactless_checkin: "Бесконтактное заселение",
    "24h_checkin": "Круглосуточное заселение",
    pets_allowed: "Можно с животными",
    parties_allowed: "Сдаётся для вечеринок",
    accounting_docs: "Отчётные документы",
    no_smoking: "Курение запрещено",
    children_allowed: "Можно с детьми",
  };

  // Квадратики: тип/комнаты, площадь, этаж
  const keySpecs = [
    {
      icon: Building2,
      label: property.typeLabel ?? PROPERTY_TYPE_LABELS[property.type] ?? property.type,
      value: showRooms(property.type) && property.specifications.rooms != null
        ? `${property.specifications.rooms}-комн.`
        : (property.typeLabel ?? PROPERTY_TYPE_LABELS[property.type] ?? property.type),
    },
    property.type === "land"
      ? { icon: Maximize, label: "Площадь участка", value: property.specifications.landArea ? `${property.specifications.landArea} сот.` : "-" }
      : { icon: Maximize, label: "Площадь общая", value: `${property.specifications.area} м²` },
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
  ].filter((spec) => spec.value !== "-");

  // Полная таблица «О доме»
  const houseInfoSpecs = [
    ...(showBathrooms(property.type) && property.specifications.bathrooms != null
      ? [{ icon: Bath, label: "Санузлы", value: String(property.specifications.bathrooms) }]
      : []),
    ...(showYearBuilt(property.type) && property.specifications.yearBuilt != null
      ? [{ icon: Calendar, label: "Год постройки", value: String(property.specifications.yearBuilt) }]
      : []),
    ...(showRenovation(property.type) && property.specifications.renovation
      ? [{ icon: CheckCircle, label: "Ремонт", value: property.specifications.renovation }]
      : []),
    ...(showBalcony(property.type) && property.specifications.balcony
      ? [{ icon: CheckCircle, label: "Балкон / лоджия", value: property.specifications.balcony }]
      : []),
    ...(showLivingArea(property.type) && property.specifications.livingArea != null
      ? [{ icon: Maximize, label: "Жилая площадь", value: `${property.specifications.livingArea} м²` }]
      : []),
    ...(showKitchenArea(property.type) && property.specifications.kitchenArea != null
      ? [{ icon: Maximize, label: "Площадь кухни", value: `${property.specifications.kitchenArea} м²` }]
      : []),
    ...(property.type === "house" && property.specifications.landArea != null
      ? [{ icon: MapPin, label: "Площадь участка", value: `${property.specifications.landArea} сот.` }]
      : []),
    ...(showRoomDealFields(property.type, property.dealType) && property.specifications.roomsInDeal != null
      ? [{ icon: BedDouble, label: "Комнат в сделке", value: String(property.specifications.roomsInDeal) }]
      : []),
    ...(showRoomDealFields(property.type, property.dealType) && property.specifications.roomsArea != null
      ? [{ icon: Maximize, label: "Площадь комнат в сделке", value: `${property.specifications.roomsArea} м²` }]
      : []),
    ...(showDealConditions(property.dealType) && (property.specifications.dealConditions?.length ?? 0) > 0
      ? [{
          icon: CheckCircle,
          label: "Условия сделки",
          value: property.specifications.dealConditions!
            .map((cond) => DEAL_CONDITION_LABELS[cond] ?? cond)
            .join(", "),
        }]
      : []),
  ].filter((spec) => spec.value !== "-");

  const hasCheckInInfo = property.dealType === "daily" && (
    property.specifications.checkInTime ||
    property.specifications.checkOutTime ||
    (property.specifications.maxDailyGuests != null) ||
    (property.specifications.dailySingleBeds != null) ||
    (property.specifications.dailyDoubleBeds != null) ||
    (property.specifications.dealConditions?.length ?? 0) > 0
  );

  const paymentMethods = property.specifications.paymentMethods ?? [];
  const hasPaymentMethods = paymentMethods.length > 0;

  const activeAmenities = property.amenities ?? [];
  const hasAmenities = activeAmenities.length > 0;

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
      <main>
        <div className="container mx-auto px-4 py-4">
          <Link href={backToCatalogHref} className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors">
            <ArrowLeft className="w-4 h-4" />
            Назад к объявлениям
          </Link>
        </div>

        <section className="container mx-auto px-4 mb-8">
          <div className="grid grid-cols-1 md:grid-cols-4 md:grid-rows-2 gap-2 rounded-2xl overflow-hidden max-h-[500px]">
            <motion.div
              className="md:col-span-2 md:row-span-2 relative cursor-pointer group aspect-[4/3] w-full min-h-0 overflow-hidden md:min-h-0"
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
              {mainImageClearlyLandscape === true ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={images[0]}
                  alt="Главное фото"
                  className="hidden md:block absolute inset-0 h-full w-full object-cover"
                />
              ) : (
                <GalleryPortraitFrame
                  src={images[0]}
                  alt="Главное фото"
                  className="absolute inset-0 hidden md:flex"
                />
              )}
              <div className="md:hidden absolute inset-0 overflow-hidden">
                <GalleryPortraitFrame
                  src={images[currentImage]}
                  alt={`Фото ${currentImage + 1}`}
                />
              </div>
              <div className="absolute inset-0 z-[2] bg-foreground/0 group-hover:bg-foreground/10 transition-colors" />
            </motion.div>
            {images.slice(1, 5).map((img, i) => (
              <div
                key={i}
                className="relative cursor-pointer group hidden md:block overflow-hidden"
                onClick={() => { setCurrentImage(i + 1); setLightboxOpen(true); }}
              >
                <GalleryGridThumb src={img} alt={`Фото ${i + 2}`} />
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
              className="cursor-pointer p-1.5 rounded-full bg-muted touch-manipulation transition-[transform,background-color,color] duration-150 ease-out active:scale-95 active:bg-primary/25 active:text-primary"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
            <span className="text-sm text-muted-foreground">{currentImage + 1} / {images.length}</span>
            <button
              type="button"
              aria-label="Следующее фото"
              onClick={nextImage}
              className="cursor-pointer p-1.5 rounded-full bg-muted touch-manipulation transition-[transform,background-color,color] duration-150 ease-out active:scale-95 active:bg-primary/25 active:text-primary"
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
                      className={`p-2.5 rounded-xl transition-colors cursor-pointer ${
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
                      className="p-2.5 rounded-xl bg-muted hover:bg-muted/80 transition-colors cursor-pointer"
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
                <div className="flex items-baseline gap-2 flex-wrap">
                  {property.dealType === "daily" && (
                    <span className="text-sm text-muted-foreground">от</span>
                  )}
                  <span className="text-3xl font-bold text-primary">
                    <PriceDisplay amount={priceDisplay.primaryAmount} currency={priceDisplay.primaryCurrency} />
                  </span>
                  {property.dealType === "daily" && (
                    <span className="text-sm text-muted-foreground">/ сутки</span>
                  )}
                </div>
                {property.dealType === "daily" && property.weekendPriceNegotiable && (
                  <p className="text-sm text-muted-foreground mt-1">
                    В выходные и праздничные дни цена договорная
                  </p>
                )}
              </motion.div>

              {/* Key specs — 4 compact squares */}
              <motion.div
                initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1, duration: 0.5 }}
                className="grid gap-2"
                style={{ gridTemplateColumns: `repeat(${keySpecs.length}, minmax(0, 1fr))` }}
              >
                {keySpecs.map((spec) => (
                  <div key={spec.label} className="bg-muted rounded-xl px-3 py-3 text-center">
                    <spec.icon className="w-4 h-4 mx-auto text-primary mb-1.5" />
                    <p className="text-[10px] text-muted-foreground leading-tight mb-0.5">{spec.label}</p>
                    <p className="text-sm font-semibold text-foreground">{spec.value}</p>
                  </div>
                ))}
              </motion.div>

              {/* Check-in / house rules (daily only) */}
              {hasCheckInInfo && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-3">Условия заселения</h2>
                  <div className="rounded-xl border border-border/50 overflow-hidden max-w-lg">
                    {property.specifications.checkInTime && (
                      <div className="flex items-center justify-between px-4 py-3 bg-muted/20">
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <LogIn className="w-4 h-4 text-primary/70" />
                          Заезд не ранее
                        </span>
                        <span className="text-sm font-semibold text-foreground">{property.specifications.checkInTime}</span>
                      </div>
                    )}
                    {property.specifications.checkOutTime && (
                      <div className="flex items-center justify-between px-4 py-3 border-t border-border/40">
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <LogOut className="w-4 h-4 text-primary/70" />
                          Выезд до
                        </span>
                        <span className="text-sm font-semibold text-foreground">{property.specifications.checkOutTime}</span>
                      </div>
                    )}
                    {property.specifications.maxDailyGuests != null && (
                      <div className="flex items-center justify-between px-4 py-3 border-t border-border/40 bg-muted/20">
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <Users className="w-4 h-4 text-primary/70" />
                          Максимум гостей
                        </span>
                        <span className="text-sm font-semibold text-foreground">{property.specifications.maxDailyGuests}</span>
                      </div>
                    )}
                    {property.specifications.dailySingleBeds != null && (
                      <div className="flex items-center justify-between px-4 py-3 border-t border-border/40">
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <BedDouble className="w-4 h-4 text-primary/70" />
                          Односпальных кроватей
                        </span>
                        <span className="text-sm font-semibold text-foreground">{property.specifications.dailySingleBeds}</span>
                      </div>
                    )}
                    {property.specifications.dailyDoubleBeds != null && (
                      <div className="flex items-center justify-between px-4 py-3 border-t border-border/40 bg-muted/20">
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <BedDouble className="w-4 h-4 text-primary/70" />
                          Двуспальных кроватей
                        </span>
                        <span className="text-sm font-semibold text-foreground">{property.specifications.dailyDoubleBeds}</span>
                      </div>
                    )}
                    {(property.specifications.dealConditions?.length ?? 0) > 0 && (
                      <div className="px-4 py-3 border-t border-border/40">
                        <div className="flex flex-wrap gap-2">
                          {property.specifications.dealConditions!.map((cond) => (
                            <span key={cond} className="inline-flex items-center gap-1.5 bg-primary/10 text-primary rounded-lg px-3 py-1.5 text-sm font-medium">
                              <UserCheck className="w-3.5 h-3.5 flex-shrink-0" />
                              {DEAL_CONDITION_LABELS[cond] ?? cond}
                            </span>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </motion.div>
              )}

              {hasPaymentMethods && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.22, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-3">Способы оплаты</h2>
                  <div className="flex flex-wrap gap-2">
                    {paymentMethods.map((method) => (
                      <span
                        key={method}
                        className="inline-flex items-center gap-1.5 bg-primary/10 text-primary rounded-lg px-3 py-1.5 text-sm font-medium"
                      >
                        <Wallet className="w-3.5 h-3.5 flex-shrink-0" />
                        {PAYMENT_METHOD_LABELS[method as PaymentMethodId] ?? method}
                      </span>
                    ))}
                  </div>
                </motion.div>
              )}

              {/* Amenities */}
              {hasAmenities && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.25, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-3">Удобства</h2>
                  <div className="space-y-5">
                    {LISTING_AMENITY_GROUPS.map((group) => {
                      const visibleItems = group.items.filter(
                        (item) =>
                          activeAmenities.includes(item.id) &&
                          (!item.propertyTypes || item.propertyTypes.includes(property.type))
                      );
                      if (visibleItems.length === 0) return null;
                      return (
                        <div key={group.id}>
                          <p className="text-sm font-semibold text-muted-foreground mb-2">{group.title}</p>
                          <div className="flex flex-wrap gap-2">
                            {visibleItems.map((item) => {
                              const Icon = AMENITY_ICON_MAP[item.id] ?? CheckCircle;
                              return (
                                <span
                                  key={item.id}
                                  className="inline-flex items-center gap-1.5 bg-muted rounded-lg px-3 py-1.5 text-sm text-foreground"
                                >
                                  <Icon className="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                  {item.label}
                                </span>
                              );
                            })}
                          </div>
                        </div>
                      );
                    })}
                    {(() => {
                      const knownIds = LISTING_AMENITY_GROUPS.flatMap((g) => g.items.map((i) => i.id));
                      const unknownAmenities = activeAmenities.filter((id) => !knownIds.includes(id));
                      if (!unknownAmenities.length) return null;
                      return (
                        <div className="flex flex-wrap gap-2">
                          {unknownAmenities.map((id) => (
                            <span key={id} className="inline-flex items-center gap-1.5 bg-muted rounded-lg px-3 py-1.5 text-sm text-foreground">
                              <CheckCircle className="w-3.5 h-3.5 text-primary flex-shrink-0" />
                              {id}
                            </span>
                          ))}
                        </div>
                      );
                    })()}
                  </div>
                </motion.div>
              )}

              {/* О доме — detailed specs table */}
              {houseInfoSpecs.length > 0 && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-3">О доме</h2>
                  <div className="rounded-xl border border-border/50 overflow-hidden max-w-lg">
                    {houseInfoSpecs.map((spec, i) => (
                      <div
                        key={spec.label}
                        className={`flex items-center justify-between px-4 py-3 ${i > 0 ? "border-t border-border/40" : ""} ${i % 2 === 0 ? "bg-muted/20" : ""}`}
                      >
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <spec.icon className="w-4 h-4 text-primary/70 flex-shrink-0" />
                          {spec.label}
                        </span>
                        <span className="text-sm font-medium text-foreground">{spec.value}</span>
                      </div>
                    ))}
                  </div>
                </motion.div>
              )}

              {/* Описание — после удобств */}
              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3, duration: 0.5 }}>
                <h2 className="text-xl font-bold text-foreground mb-4">Описание</h2>
                <div className="text-muted-foreground leading-relaxed space-y-3 whitespace-pre-line">
                  {property.description}
                </div>
              </motion.div>

              {property.status === "published" && (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.32, duration: 0.5 }}
                  className="rounded-2xl border border-border/50 bg-card/30 p-5 md:p-6"
                >
                  <h2 className="text-xl font-bold text-foreground mb-2">Отзывы</h2>
                  {reviewsLoading ? (
                    <div className="h-24 animate-pulse rounded-lg bg-muted/50" />
                  ) : (
                    <>
                      <ReviewSummary
                        ratingAvg={reviewsData?.ratingAvg ?? property.ratingAvg ?? null}
                        reviewCount={reviewsData?.reviewCount ?? property.reviewCount ?? 0}
                      />
                      <ReviewList items={reviewsData?.items ?? []} />
                      {isOwner && loggedIn && (
                        <p className="mt-4 text-sm text-muted-foreground">
                          На собственное объявление нельзя оставить отзыв.
                        </p>
                      )}
                      {hasPendingOwnReview && viewerReview && (
                        <div className="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/5 px-4 py-3 text-sm text-foreground">
                          <p className="mb-2">Ваш отзыв отправлен на модерацию.</p>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={deleteReviewMutation.isPending}
                            onClick={() => {
                              deleteReviewMutation.mutate(viewerReview.id, {
                                onSuccess: () => toast.success("Черновик отзыва удалён"),
                                onError: () => toast.error("Не удалось удалить отзыв"),
                              });
                            }}
                          >
                            Удалить черновик
                          </Button>
                        </div>
                      )}
                      {hasApprovedOwnReview && (
                        <p className="mt-4 text-sm text-muted-foreground">Спасибо, вы уже оставили отзыв об этом объекте.</p>
                      )}
                      {canLeaveReview && <ReviewForm propertyId={property.id} />}
                      {!loggedIn && property.status === "published" && !isOwner && (
                        <p className="mt-4 text-sm text-muted-foreground">
                          <Link href={loginWithReturnHref} className="text-primary font-medium underline-offset-4 hover:underline">
                            Войдите
                          </Link>
                          , чтобы оставить отзыв.
                        </p>
                      )}
                    </>
                  )}
                </motion.div>
              )}

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
                  {!phoneRevealed ? (
                    <Button
                      className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0 h-11"
                      disabled={!hasContactPhones}
                      onClick={() => {
                        if (!hasContactPhones) return;
                        void trackPhoneView(property.id);
                        setPhoneRevealed(true);
                      }}
                    >
                      <Phone className="w-4 h-4 mr-2" />
                      {!hasContactPhones
                        ? "Телефон не указан"
                        : `${maskContactPhone(primaryContactPhone)} · Показать`}
                    </Button>
                  ) : (
                    <>
                      {contactPhones.map((entry, index) => {
                        const showTelegram = index === 0 && !!contactTelegram;
                        const showMessengers =
                          entry.hasViber || entry.hasWhatsapp || showTelegram;

                        return (
                          <div key={`${entry.phone}-${index}`} className="flex gap-2">
                            <Button
                              className="min-w-0 flex-1 basis-0 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0 h-11"
                              asChild
                            >
                              <a href={phoneToTelHref(entry.phone)}>
                                <Phone className="w-4 h-4 shrink-0" />
                                <span className="truncate">{entry.phone}</span>
                              </a>
                            </Button>
                            <div className="flex w-[9.25rem] shrink-0 justify-start gap-2">
                            {showMessengers && (
                              <>
                                {entry.hasViber && (
                                  <Button
                                    variant="outline"
                                    size="icon"
                                    className="h-11 w-11 shrink-0 hover:bg-muted"
                                    asChild
                                  >
                                    <a
                                      href={viberChatHref(entry.phone)}
                                      target="_blank"
                                      rel="noopener noreferrer"
                                      aria-label="Написать в Viber"
                                    >
                                      <ViberIcon className="h-7 w-7" />
                                    </a>
                                  </Button>
                                )}
                                {entry.hasWhatsapp && (
                                  <Button
                                    variant="outline"
                                    size="icon"
                                    className="h-11 w-11 shrink-0 text-[#25D366] hover:text-[#25D366] hover:bg-[#25D366]/10"
                                    asChild
                                  >
                                    <a
                                      href={whatsAppHref(entry.phone)}
                                      target="_blank"
                                      rel="noopener noreferrer"
                                      aria-label="Написать в WhatsApp"
                                    >
                                      <WhatsAppIcon className="!h-7 !w-7" />
                                    </a>
                                  </Button>
                                )}
                                {showTelegram && (
                                  <Button
                                    variant="outline"
                                    size="icon"
                                    className="h-11 w-11 shrink-0 text-[#229ED9] hover:text-[#229ED9] hover:bg-[#229ED9]/10"
                                    asChild
                                  >
                                    <a
                                      href={telegramHref(contactTelegram)}
                                      target="_blank"
                                      rel="noopener noreferrer"
                                      aria-label="Написать в Telegram"
                                    >
                                      <TelegramIcon className="!h-7 !w-7" />
                                    </a>
                                  </Button>
                                )}
                              </>
                            )}
                            </div>
                          </div>
                        );
                      })}
                    </>
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
                  {!isOwner && canBookInquiry && (
                    <Button
                      variant="outline"
                      className="w-full h-11"
                      onClick={() => setBookingOpen(true)}
                    >
                      <CalendarCheck className="w-4 h-4 mr-2" />
                      Забронировать
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
              className="cursor-pointer absolute top-4 right-4 p-2 text-background/70 transition-[opacity,transform,color] duration-150 hover:text-background active:scale-95 active:text-primary active:opacity-100"
            >
              <X className="w-6 h-6" />
            </button>
            <button
              type="button"
              aria-label="Предыдущее фото"
              onClick={(e) => { e.stopPropagation(); prevImage(); }}
              className="cursor-pointer absolute left-4 touch-manipulation rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
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
              className="cursor-pointer absolute right-4 touch-manipulation rounded-full bg-background/10 p-2 text-background transition-[transform,background-color,color] duration-150 hover:bg-background/20 active:scale-95 active:bg-primary active:text-primary-foreground"
            >
              <ChevronRight className="w-6 h-6" />
            </button>
            <div className="absolute bottom-6 text-background/70 text-sm">
              {currentImage + 1} / {images.length}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {canBookInquiry && (
        <BookingInquiryModal
          key={bookingOpen ? `booking-${property.id}` : 'booking-closed'}
          open={bookingOpen}
          onOpenChange={setBookingOpen}
          property={property}
        />
      )}
    </div>
  );
}
