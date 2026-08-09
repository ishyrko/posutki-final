'use client';

import { useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  Heart, Share2, MapPin, BedDouble, Bath, Maximize,
  Building2, Calendar, Layers, MessageCircle, TrainFront,
  ChevronLeft, ChevronRight, Shield, CheckCircle,
  Users, Utensils, Wifi, Tv, Sofa, Car, Waves, Wind,
  ShowerHead, Flame, Coffee, Snowflake, Baby, WashingMachine,
  LogIn, LogOut, UserCheck, Sunrise, Wallet,
} from "lucide-react";
import { LISTING_AMENITY_GROUPS } from "@/features/create-listing/listing-amenity-groups";
import { formatMinStayDays } from "@/features/create-listing/validation";
import { PAYMENT_METHOD_LABELS, type PaymentMethodId } from "@/features/properties/payment-methods";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import dynamic from "next/dynamic";
import { notFound, usePathname } from "next/navigation";
import { useHasAuthToken } from "@/hooks/useHasAuthToken";
import { useProperty, useFavoriteIds, useToggleFavorite, useExchangeRates } from "@/features/properties/hooks";
import { trackPropertyView } from "@/features/properties/api";
import { trackViewOnce } from "@/lib/view-tracking";
import { useUser } from "@/features/auth/hooks";
import { formatAddress, Property } from "@/features/properties/types";
import { PropertyAddress } from "@/features/properties/components/PropertyAddress";
import { getVideoEmbedInfo } from "@/features/properties/lib/videoEmbed";
import { PropertyVideoPlayer } from "@/features/properties/components/PropertyVideoPlayer";
import { formatPropertyDealHeading } from "@/features/properties/property-deal-heading";
import type { ExchangeRates } from "@/features/properties/api";
import { PriceDisplay, BynCurrencyMark } from "@/components/BynCurrency";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "@/features/properties/price-display";
import { useCurrency } from "@/context/CurrencyContext";
import { useNearViewport } from "@/hooks/useNearViewport";
import { BookingInquiryModal } from "@/features/properties/components/BookingInquiryModal";
import {
  getPropertySellerName,
  PropertyOwnerContactPanel,
} from "@/features/properties/components/PropertyOwnerContactPanel";
import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerHeader,
  DrawerTitle,
} from "@/components/ui/drawer";

const PropertyMap = dynamic(() => import("@/components/PropertyMap"), {
  ssr: false,
  loading: () => <div className="w-full h-full bg-muted/40 animate-pulse" aria-hidden />,
});
import { OwnerOtherListings } from "@/features/properties/components/OwnerOtherListings";
import PropertyNearbyLandmarks from "@/features/properties/components/PropertyNearbyLandmarks";
import { PropertyLightbox } from "@/features/properties/components/PropertyLightbox";
import { PropertyMobileGallery } from "@/features/properties/components/PropertyMobileGallery";
import {
  GalleryGridThumb,
  GalleryPortraitFrame,
  useImageClearlyLandscape,
} from "@/features/properties/components/property-gallery-frames";
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
import { PropertyAvailabilityCalendar } from "@/features/properties/components/PropertyAvailabilityCalendar";
import { isCalendarRecentlyActive } from "@/features/properties/property-calendar-utils";
import { ReviewForm } from "@/features/reviews/components/ReviewForm";
import { ReviewList } from "@/features/reviews/components/ReviewList";
import { ReviewSummary } from "@/features/reviews/components/ReviewSummary";
import { useDeletePendingReview, usePropertyReviews } from "@/features/reviews/hooks";
type PropertyDetailClientProps = {
  id: number;
  initialProperty: Property;
  children?: ReactNode;
};

const PROPERTY_TYPE_LABELS: Record<string, string> = {
  apartment: "Квартира",
  house: "Дом",
};

export default function PropertyDetailClient({
  id,
  initialProperty,
  children,
}: PropertyDetailClientProps) {
  const ssrFetchedAtRef = useRef(Date.now());
  const { data: property, isLoading, isError } = useProperty(id, {
    initialData: initialProperty,
    initialDataUpdatedAt: ssrFetchedAtRef.current,
  });
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
  const [bookingOpen, setBookingOpen] = useState(false);
  const [contactOpen, setContactOpen] = useState(false);

  useEffect(() => {
    if (!property) {
      return;
    }

    // Backend returns 404 for non-published listings; owners preview moderation/etc. without counting.
    if (property.status !== "published") {
      return;
    }

    const isListingOwner =
      currentUser?.id != null &&
      property.ownerId != null &&
      Number(currentUser.id) === Number(property.ownerId);
    if (isListingOwner) {
      return;
    }

    void trackViewOnce(`property:${property.id}`, (visitorId) =>
      trackPropertyView(property.id, visitorId),
    ).catch(() => null);
  }, [property, currentUser?.id]);

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

  if (
    isError ||
    !property ||
    property.status === "archived" ||
    property.status === "deleted"
  ) {
    notFound();
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

  const sellerName = getPropertySellerName(property);
  const canBookInquiry = property.contact?.hasEmail === true;
  const allowsMessagesAndInquiries = property.contact?.allowsMessagesAndInquiries !== false;
  const allowsGuestInquiries = property.contact?.allowsGuestInquiries !== false;
  const canSubmitBookingInquiry = canBookInquiry && allowsMessagesAndInquiries && (loggedIn || allowsGuestInquiries);
  const showMobileContactBar = !isOwner;

  const images = property.images?.map(img => img.url) || [
    "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80",
  ];
  const showExtraPhotosOverlay = images.length > 5;
  const extraPhotoCount = images.length - 4;
  const videoEmbed = useMemo(() => getVideoEmbedInfo(property.videoUrl), [property.videoUrl]);
  const addressStr = formatAddress(property.address);
  const coords = property.coordinates;
  const { ref: mapSectionRef, isNear: isMapNear } = useNearViewport();
  const nearbyMetroStations = property.nearbyMetroStations ?? [];
  const nearbyLandmarks = property.nearbyLandmarks ?? [];

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
    (property.specifications.minStayDays != null && property.specifications.minStayDays > 1) ||
    (property.specifications.dealConditions?.length ?? 0) > 0
  );

  const paymentMethods = property.specifications.paymentMethods ?? [];
  const hasPaymentMethods = paymentMethods.length > 0;

  const additionalServices = (property.additionalServices ?? []).filter(
    (svc) => svc.name.trim() !== "" && Number.isFinite(svc.price),
  );
  const hasAdditionalServices = property.type === "house" && additionalServices.length > 0;

  const activeAmenities = property.amenities ?? [];
  const hasAmenities = activeAmenities.length > 0;

  const prevImage = () => setCurrentImage((p) => (p === 0 ? images.length - 1 : p - 1));
  const nextImage = () => setCurrentImage((p) => (p === images.length - 1 ? 0 : p + 1));
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
    <div className={`min-h-screen bg-background${showMobileContactBar ? " max-lg:pb-[calc(8.5rem+env(safe-area-inset-bottom,0px))]" : ""}`}>
      <main className="min-w-0">
        {children ? (
          <div className="container mx-auto min-w-0 px-4 pb-1 pt-2 sm:py-4">{children}</div>
        ) : null}

        <section className="container mx-auto min-w-0 px-4 mb-8">
          <div className="grid grid-cols-1 md:grid-cols-4 md:grid-rows-2 gap-2 rounded-2xl overflow-hidden max-h-[500px]">
            <motion.div
              className="md:col-span-2 md:row-span-2 relative cursor-pointer group aspect-[4/3] w-full min-h-0 overflow-hidden md:min-h-0"
              onClick={() => setLightboxOpen(true)}
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
              <div className="md:hidden absolute inset-0">
                <PropertyMobileGallery
                  images={images}
                  currentIndex={currentImage}
                  onIndexChange={setCurrentImage}
                  onOpenLightbox={() => setLightboxOpen(true)}
                />
              </div>
              <div className="pointer-events-none absolute inset-0 z-[2] bg-foreground/0 group-hover:bg-foreground/10 transition-colors" />
            </motion.div>
            {images.slice(1, 5).map((img, i) => (
              <div
                key={i}
                className="relative cursor-pointer group hidden md:block overflow-hidden"
                onClick={() => { setCurrentImage(i + 1); setLightboxOpen(true); }}
              >
                <GalleryGridThumb src={img} alt={`Фото ${i + 2}`} preferCover={i === 3 && showExtraPhotosOverlay} />
                <div className="absolute inset-0 z-[1] bg-foreground/0 group-hover:bg-foreground/10 transition-colors pointer-events-none" />
                {i === 3 && showExtraPhotosOverlay && (
                  <div className="absolute inset-0 z-[2] flex items-center justify-center bg-foreground/50 pointer-events-none">
                    <span className="text-primary-foreground font-medium text-sm">Ещё {extraPhotoCount} фото</span>
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
            <div className="md:hidden mt-3 min-w-0 max-w-full overflow-hidden">
              <div className="flex items-center justify-center gap-1.5 overflow-x-auto scrollbar-hide px-1">
                {images.map((_, index) => (
                  <button
                    key={index}
                    type="button"
                    onClick={() => setCurrentImage(index)}
                    aria-label={`Перейти к фото ${index + 1}`}
                    className={`h-2 shrink-0 rounded-full transition-all ${
                      currentImage === index
                        ? "w-5 bg-primary"
                        : "w-2 bg-muted-foreground/40 hover:bg-muted-foreground/60"
                    }`}
                  />
                ))}
              </div>
            </div>
          )}
        </section>

        <div className="container mx-auto min-w-0 px-4 pb-16">
          <div className="grid min-w-0 grid-cols-1 gap-8 lg:grid-cols-3">
            <div className="min-w-0 space-y-8 lg:col-span-2">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
                <div className="mb-3 flex min-w-0 items-start justify-between gap-4">
                  <div className="min-w-0">
                    <span className="inline-block px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-medium mb-3">
                      {formatPropertyDealHeading(property.dealType, property.type)}
                    </span>
                    <h1 className="text-2xl md:text-3xl font-bold text-foreground">{property.title}</h1>
                  </div>
                  <div className="flex gap-2 shrink-0">
                    <button
                      type="button"
                      onClick={() => {
                        toggleFavorite({
                          propertyId: id,
                          isFavorited,
                          property: {
                            type: property.type,
                            address: { cityName: property.address?.cityName },
                          },
                        });
                      }}
                      aria-label={isFavorited ? "Убрать из избранного" : "Добавить в избранное"}
                      aria-pressed={isFavorited}
                      className={`p-2.5 rounded-xl transition-colors cursor-pointer ${
                        isFavorited
                          ? 'bg-primary/10 hover:bg-primary/20'
                          : 'bg-muted hover:bg-muted/80'
                      }`}
                    >
                      <Heart aria-hidden="true" className={`w-5 h-5 ${isFavorited ? 'fill-primary text-primary' : 'text-muted-foreground'}`} />
                    </button>
                    <button
                      type="button"
                      onClick={() => { void handleShare(); }}
                      aria-label="Поделиться объявлением"
                      className="p-2.5 rounded-xl bg-muted hover:bg-muted/80 transition-colors cursor-pointer"
                    >
                      <Share2 aria-hidden="true" className="w-5 h-5 text-muted-foreground" />
                    </button>
                  </div>
                </div>
                <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:gap-3">
                  <p className="flex min-w-0 items-start gap-1.5 text-muted-foreground md:items-center">
                    <MapPin className="mt-0.5 h-4 w-4 shrink-0 md:mt-0" />
                    <span className="min-w-0 break-words">
                      <PropertyAddress address={property.address} propertyType={property.type} />
                    </span>
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
                    {property.specifications.minStayDays != null && property.specifications.minStayDays > 1 && (
                      <div className="flex items-center justify-between px-4 py-3 border-t border-border/40">
                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                          <Calendar className="w-4 h-4 text-primary/70" />
                          Минимальный срок проживания
                        </span>
                        <span className="text-sm font-semibold text-foreground">
                          {formatMinStayDays(property.specifications.minStayDays)}
                        </span>
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

              {hasAdditionalServices && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.23, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-3">Дополнительные услуги</h2>
                  <div className="rounded-xl border border-border/50 overflow-hidden max-w-lg">
                    {additionalServices.map((svc, i) => (
                      <div
                        key={`${svc.name}-${i}`}
                        className={`flex items-center justify-between gap-4 px-4 py-3 ${i > 0 ? "border-t border-border/40" : ""} ${i % 2 === 0 ? "bg-muted/20" : ""}`}
                      >
                        <span className="text-sm text-foreground">{svc.name}</span>
                        <span className="text-sm font-semibold text-foreground inline-flex items-baseline gap-1 shrink-0">
                          {svc.price} <BynCurrencyMark />
                        </span>
                      </div>
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
              {videoEmbed && (
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.28, duration: 0.5 }}>
                  <h2 className="text-xl font-bold text-foreground mb-4">Видео</h2>
                  <PropertyVideoPlayer embed={videoEmbed} />
                </motion.div>
              )}

              <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3, duration: 0.5 }}>
                <h2 className="text-xl font-bold text-foreground mb-4">Описание</h2>
                <div className="text-muted-foreground leading-relaxed space-y-3 whitespace-pre-line">
                  {property.description}
                </div>
              </motion.div>

              {property.type === "apartment" && nearbyLandmarks.length > 0 && property.address.citySlug ? (
                <motion.div
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.31, duration: 0.5 }}
                >
                  <PropertyNearbyLandmarks
                    citySlug={property.address.citySlug}
                    landmarks={nearbyLandmarks}
                  />
                </motion.div>
              ) : null}

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
                  <div
                    ref={mapSectionRef}
                    className="rounded-2xl overflow-hidden border border-border h-[350px]"
                  >
                    {isMapNear ? (
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
                    ) : (
                      <div className="w-full h-full bg-muted/40 animate-pulse" aria-hidden />
                    )}
                  </div>
                </motion.div>
              )}
            </div>

            <div className="space-y-4">
              {isCalendarRecentlyActive(property.calendarLastUpdatedAt) && (
                <motion.div
                  initial={{ opacity: 0, x: 20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: 0.15, duration: 0.5 }}
                >
                  <PropertyAvailabilityCalendar propertyId={property.id} className="w-full max-w-none" />
                </motion.div>
              )}

              <motion.div
                initial={{ opacity: 0, x: 20 }} animate={{ opacity: 1, x: 0 }}
                transition={{ delay: 0.2, duration: 0.5 }}
                className="hidden lg:block bg-card rounded-2xl p-6 shadow-card sticky top-20"
              >
                <PropertyOwnerContactPanel
                  property={property}
                  isOwner={isOwner}
                  loggedIn={loggedIn}
                  loginWithReturnHref={loginWithReturnHref}
                  onOpenBooking={() => setBookingOpen(true)}
                />
              </motion.div>
            </div>
          </div>
        </div>
      </main>

      {showMobileContactBar && (
        <div className="lg:hidden fixed bottom-0 left-0 right-0 z-40 border-t border-border bg-card/95 backdrop-blur-xl px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))]">
          <div className="flex items-baseline gap-1.5 flex-wrap mb-2.5">
            {property.dealType === "daily" && (
              <span className="text-sm text-muted-foreground">от</span>
            )}
            <span className="text-xl font-bold text-primary">
              <PriceDisplay amount={priceDisplay.primaryAmount} currency={priceDisplay.primaryCurrency} />
            </span>
            {property.dealType === "daily" && (
              <span className="text-sm text-muted-foreground">/ сутки</span>
            )}
          </div>
          <Button
            className="w-full h-11 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0"
            onClick={() => setContactOpen(true)}
          >
            <MessageCircle className="w-4 h-4 mr-2" />
            Связаться с владельцем
          </Button>
        </div>
      )}

      {showMobileContactBar && (
        <Drawer open={contactOpen} onOpenChange={setContactOpen}>
          <DrawerContent className="max-h-[90vh]">
            <DrawerHeader className="sr-only">
              <DrawerTitle>Связаться с владельцем</DrawerTitle>
              <DrawerDescription>Контакты владельца объявления</DrawerDescription>
            </DrawerHeader>
            <div className="overflow-y-auto px-4 pb-[calc(1rem+env(safe-area-inset-bottom,0px))]">
              <PropertyOwnerContactPanel
                property={property}
                isOwner={isOwner}
                loggedIn={loggedIn}
                loginWithReturnHref={loginWithReturnHref}
                onOpenBooking={() => {
                  setContactOpen(false);
                  setBookingOpen(true);
                }}
              />
            </div>
          </DrawerContent>
        </Drawer>
      )}

      {property.type === "apartment" && (
        <OwnerOtherListings propertyId={property.id} ownerName={sellerName} />
      )}

      <AnimatePresence>
        {lightboxOpen && (
          <PropertyLightbox
            images={images}
            currentIndex={currentImage}
            onIndexChange={setCurrentImage}
            onClose={() => setLightboxOpen(false)}
          />
        )}
      </AnimatePresence>

      {canSubmitBookingInquiry && (
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
