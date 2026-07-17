"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { motion } from "framer-motion";
import { ChevronLeft, ChevronRight } from "lucide-react";
import PropertyCard from "@/components/PropertyCard";
import { PriceDisplay } from "@/components/BynCurrency";
import { useCurrency } from "@/context/CurrencyContext";
import { useExchangeRates, useOwnerListings } from "@/features/properties/hooks";
import { formatAddress, type Property } from "@/features/properties/types";
import { propertyUrlRegionSlug } from "@/features/catalog/slugs";
import type { ExchangeRates } from "@/features/properties/api";
import {
  DEFAULT_EXCHANGE_RATES_FALLBACK,
  formatPropertyPrices,
} from "@/features/properties/price-display";

const OWNER_LISTINGS_LIMIT = 10;

const CARD_CLASS =
  "w-[min(calc(100vw-3.5rem),300px)] shrink-0 snap-start sm:w-[min(calc((100vw-3rem)/2-0.5rem),380px)] lg:w-[min(calc((min(100vw,1280px)-5rem)/4-0.75rem),320px)]";

type OwnerOtherListingsProps = {
  propertyId: number;
  ownerName: string;
};

function OwnerListingCard({
  property,
  index,
  exchangeRates,
  selectedCurrency,
}: {
  property: Property;
  index: number;
  exchangeRates: ExchangeRates;
  selectedCurrency: ReturnType<typeof useCurrency>["selectedCurrency"];
}) {
  const { primaryAmount, primaryCurrency, secondary } = formatPropertyPrices(
    property,
    exchangeRates,
    selectedCurrency,
  );

  return (
    <PropertyCard
      id={property.id}
      image={
        property.images?.[0]?.thumbnailUrl ||
        property.images?.[0]?.url ||
        "https://placehold.co/600x450?text=No+Image"
      }
      price={<PriceDisplay amount={primaryAmount} currency={primaryCurrency} />}
      primaryBynAmount={primaryAmount}
      secondaryPrice={secondary}
      title={property.title}
      address={formatAddress(property.address)}
      beds={property.specifications.rooms || 0}
      baths={property.specifications.bathrooms ?? 1}
      area={property.specifications.area || 0}
      maxGuests={property.specifications.maxDailyGuests}
      typeLabel={property.typeLabel}
      dealType={property.dealType}
      propertyType={property.type}
      regionSlug={propertyUrlRegionSlug(
        property.address.regionName,
        property.address.citySlug,
        property.type,
      )}
      index={index}
      animateEntrance={false}
      rating={property.ratingAvg ?? null}
      reviewCount={property.reviewCount ?? null}
      placementEffectiveLevel={property.placementEffectiveLevel}
    />
  );
}

function OwnerListingsCarousel({
  listings,
  exchangeRates,
  selectedCurrency,
  isLoading,
}: {
  listings: Property[];
  exchangeRates: ExchangeRates;
  selectedCurrency: ReturnType<typeof useCurrency>["selectedCurrency"];
  isLoading: boolean;
}) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);

  const checkScroll = () => {
    const el = scrollRef.current;
    if (!el) return;
    setCanScrollLeft(el.scrollLeft > 4);
    setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 4);
  };

  useEffect(() => {
    checkScroll();
    const el = scrollRef.current;
    if (!el) return;

    el.addEventListener("scroll", checkScroll, { passive: true });
    window.addEventListener("resize", checkScroll);

    return () => {
      el.removeEventListener("scroll", checkScroll);
      window.removeEventListener("resize", checkScroll);
    };
  }, [isLoading, listings.length]);

  const scroll = (dir: "left" | "right") => {
    const el = scrollRef.current;
    if (!el) return;
    const card = el.querySelector<HTMLElement>("[data-owner-listing-card]");
    const step = card ? card.offsetWidth + 16 : el.clientWidth * 0.85;
    el.scrollBy({ left: dir === "left" ? -step : step, behavior: "smooth" });
  };

  if (isLoading) {
    return (
      <div className="relative min-w-0">
        <div className="flex gap-4 overflow-hidden">
          {[...Array(4)].map((_, idx) => (
            <div key={idx} className={`h-[360px] animate-pulse rounded-2xl bg-muted/40 ${CARD_CLASS}`} />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="group relative min-w-0">
      <button
        type="button"
        onClick={() => scroll("left")}
        aria-label="Прокрутить влево"
        className={`absolute left-1 top-[42%] z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-border bg-card shadow-card transition-opacity hover:border-primary/30 hover:shadow-card-hover md:left-2 ${
          canScrollLeft ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
      >
        <ChevronLeft className="h-4 w-4 text-foreground" />
      </button>

      <button
        type="button"
        onClick={() => scroll("right")}
        aria-label="Прокрутить вправо"
        className={`absolute right-1 top-[42%] z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-border bg-card shadow-card transition-opacity hover:border-primary/30 hover:shadow-card-hover md:right-2 ${
          canScrollRight ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
      >
        <ChevronRight className="h-4 w-4 text-foreground" />
      </button>

      {canScrollLeft && (
        <div className="pointer-events-none absolute inset-y-0 left-0 z-[1] w-10 bg-gradient-to-r from-muted/30 to-transparent" />
      )}
      {canScrollRight && (
        <div className="pointer-events-none absolute inset-y-0 right-0 z-[1] w-10 bg-gradient-to-l from-muted/30 to-transparent" />
      )}

      <div
        ref={scrollRef}
        className="flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain scroll-smooth pb-1 scrollbar-hide [-webkit-overflow-scrolling:touch]"
      >
        {listings.map((property, i) => (
          <div key={property.id} data-owner-listing-card className={CARD_CLASS}>
            <OwnerListingCard
              property={property}
              index={i}
              exchangeRates={exchangeRates}
              selectedCurrency={selectedCurrency}
            />
          </div>
        ))}
      </div>
    </div>
  );
}

export function OwnerOtherListings({ propertyId, ownerName }: OwnerOtherListingsProps) {
  const { data: listings = [], isLoading } = useOwnerListings(propertyId, OWNER_LISTINGS_LIMIT);
  const { selectedCurrency } = useCurrency();
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );

  if (!isLoading && listings.length === 0) {
    return null;
  }

  const heading =
    ownerName.trim() !== "" && ownerName !== "Продавец"
      ? `Другие квартиры от ${ownerName}`
      : "Другие объявления хозяина";

  return (
    <section className="border-t border-border bg-muted/30 py-10 md:py-12">
      <div className="container mx-auto min-w-0 px-4">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="mb-6 md:mb-8"
        >
          <h2 className="font-display text-2xl font-bold text-foreground md:text-3xl">{heading}</h2>
          <p className="mt-1 text-muted-foreground">Ещё объекты этого владельца</p>
        </motion.div>

        <OwnerListingsCarousel
          listings={listings}
          exchangeRates={exchangeRates}
          selectedCurrency={selectedCurrency}
          isLoading={isLoading}
        />
      </div>
    </section>
  );
}
