import { useMemo, useRef } from "react";
import type { PropertyListResponse } from "@/features/properties/types";
import { motion } from "framer-motion";
import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import PropertyCard from "./PropertyCard";
import { useProperties, useExchangeRates } from "@/features/properties/hooks";
import { formatAddress } from "@/features/properties/types";
import { buildCatalogUrl, DEAL_TYPE_LABELS } from "@/features/catalog/slugs";
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

  const featuredInitialHydratedAt = useRef<number | null>(null);
  if (featuredInitial && featuredInitialHydratedAt.current === null) {
    featuredInitialHydratedAt.current = Date.now();
  }

  const featuredFilters = useMemo(
    () => ({
      regionSlug: effectiveRegionSlug,
      types: [...RESIDENTIAL_PROPERTY_TYPES],
      limit: 4,
      sortBy: "createdAt" as const,
      sortOrder: "DESC" as const,
    }),
    [effectiveRegionSlug],
  );

  const catalogUrl = buildCatalogUrl({ region: effectiveRegionSlug, dealType: "sale" });
  const { data, isLoading } = useProperties(
    featuredFilters,
    featuredInitial && featuredInitialHydratedAt.current !== null
      ? {
          initialData: featuredInitial,
          initialDataUpdatedAt: featuredInitialHydratedAt.current,
        }
      : undefined,
  );
  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const properties = data?.data ?? [];

  return (
    <section className="py-12 md:py-14 bg-muted/50">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="flex items-end justify-between mb-8"
        >
          <div>
            <h2 className="text-3xl font-bold text-foreground font-display mb-2">Свежие объявления</h2>
            <p className="text-muted-foreground">Последние публикации в выбранном регионе</p>
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
              const { primary, secondary } = formatPropertyPrices(property, exchangeRates);
              return (
              <PropertyCard
                key={property.id}
                id={property.id}
                image={property.images?.[0]?.thumbnailUrl || property.images?.[0]?.url || "https://placehold.co/600x450?text=No+Image"}
                price={primary}
                secondaryPrice={secondary}
                title={property.title}
                address={formatAddress(property.address)}
                beds={property.specifications.rooms || 0}
                baths={property.specifications.bathrooms ?? 1}
                area={property.specifications.area || 0}
                tag={DEAL_TYPE_LABELS[property.dealType] ?? property.dealType}
                dealType={property.dealType}
                propertyType={property.type}
                index={i}
                animateEntrance={false}
              />
            );
            })}
          </div>
        ) : (
          <div className="text-center py-12 rounded-2xl border border-dashed border-border/60 bg-card">
            <p className="text-muted-foreground">В этом регионе пока нет объявлений.</p>
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
