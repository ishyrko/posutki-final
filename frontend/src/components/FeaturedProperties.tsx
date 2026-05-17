import { useMemo } from "react";
import type { PropertyListResponse } from "@/features/properties/types";
import { motion } from "framer-motion";
import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import PropertyCard from "./PropertyCard";
import { PriceDisplay } from "@/components/BynCurrency";
import { useCurrency } from "@/context/CurrencyContext";
import { useProperties, useExchangeRates } from "@/features/properties/hooks";
import { formatAddress } from "@/features/properties/types";
import { buildCatalogUrl, propertyUrlRegionSlug, REGION_SLUGS } from "@/features/catalog/slugs";
import { RESIDENTIAL_PROPERTY_TYPES } from "@/features/catalog/residential-types";
import type { ExchangeRates } from "@/features/properties/api";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";
import {
  DEFAULT_EXCHANGE_RATES_FALLBACK,
  formatPropertyPrices,
} from "@/features/properties/price-display";

interface FeaturedPropertiesProps {
  regionSlug?: string;
  /** Prefetched on the server — avoids empty-then-swap and stale initialData refetch flicker. */
  featuredInitial?: PropertyListResponse;
}

const FeaturedProperties = ({ regionSlug, featuredInitial }: FeaturedPropertiesProps) => {
  /** Главная `/` не передаёт регион с сервера — берём тот же slug, что и шапка (по умолчанию Минская область). */
  const headerRegionSlug = useHeaderRegionSlug();
  const effectiveRegionSlug = regionSlug ?? headerRegionSlug;

  const featuredFilters = useMemo(
    () => ({
      regionSlug: effectiveRegionSlug,
      dealType: "daily" as const,
      types: [...RESIDENTIAL_PROPERTY_TYPES],
      limit: 4,
      sortBy: "createdAt" as const,
      sortOrder: "DESC" as const,
    }),
    [effectiveRegionSlug],
  );

  const catalogUrl = REGION_SLUGS.has(effectiveRegionSlug)
    ? buildCatalogUrl({ region: effectiveRegionSlug, propertyType: "apartment" })
    : buildCatalogUrl({ propertyType: "apartment" });
  const { data, isLoading } = useProperties(
    featuredFilters,
    featuredInitial ? { initialData: featuredInitial } : undefined,
  );
  const { selectedCurrency } = useCurrency();
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const properties = data?.data ?? [];

  return (
    <section className="bg-muted/50 pt-8 pb-10 md:pt-6 md:pb-12 lg:pt-8 lg:pb-14">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="flex items-end justify-between mb-6 md:mb-8"
        >
          <div>
            <h2 className="text-3xl font-bold text-foreground font-display mb-2">Свежие объявления</h2>
            <p className="text-muted-foreground">Последние размещенные объявления</p>
          </div>
          <Button asChild variant="ghost" className="hidden sm:flex items-center gap-2 text-primary hover:text-primary">
            <Link href={catalogUrl}>
              Все объекты
              <ArrowRight className="w-4 h-4" />
            </Link>
          </Button>
        </motion.div>

        {isLoading ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {[...Array(4)].map((_, idx) => (
              <div key={idx} className="h-[360px] bg-muted/40 animate-pulse rounded-2xl" />
            ))}
          </div>
        ) : properties.length > 0 ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {properties.map((property, i) => {
              const { primaryAmount, primaryCurrency, secondary } = formatPropertyPrices(
                property,
                exchangeRates,
                selectedCurrency,
              );
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
                typeLabel={property.typeLabel}
                dealType={property.dealType}
                propertyType={property.type}
                regionSlug={propertyUrlRegionSlug(property.address.regionName, property.address.citySlug)}
                index={i}
                animateEntrance={false}
                rating={property.ratingAvg ?? null}
                reviewCount={property.reviewCount ?? null}
              />
            );
            })}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center text-center py-12 px-4 rounded-2xl border border-dashed border-border/60 bg-card">
            <Button
              asChild
              size="lg"
              className="max-w-full whitespace-normal h-auto min-h-11 py-3 px-5 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
            >
              <Link href="/razmestit/">Разместить квартиру на сутки бесплатно</Link>
            </Button>
          </div>
        )}

        <div className="mt-8 text-center sm:hidden">
          <Button asChild variant="outline" className="gap-2">
            <Link href={catalogUrl}>
              Все объекты
              <ArrowRight className="w-4 h-4" />
            </Link>
          </Button>
        </div>
      </div>
    </section>
  );
};

export default FeaturedProperties;
