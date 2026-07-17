'use client';

import { useMemo } from 'react';
import PropertyCard from '@/components/PropertyCard';
import { PriceDisplay } from '@/components/BynCurrency';
import { useCurrency } from '@/context/CurrencyContext';
import { propertyUrlRegionSlug } from '@/features/catalog/slugs';
import type { ExchangeRates } from '@/features/properties/api';
import { useExchangeRates } from '@/features/properties/hooks';
import {
    DEFAULT_EXCHANGE_RATES_FALLBACK,
    formatPropertyPrices,
} from '@/features/properties/price-display';
import { formatAddress, type Property } from '@/features/properties/types';

type CatalogPropertyCardProps = {
    property: Property;
    index?: number;
    showTypeBadge?: boolean;
};

export function CatalogPropertyCard({
    property,
    index = 0,
    showTypeBadge = true,
}: CatalogPropertyCardProps) {
    const { selectedCurrency } = useCurrency();
    const { data: rates } = useExchangeRates();
    const exchangeRates: ExchangeRates = useMemo(
        () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
        [rates],
    );
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
                'https://placehold.co/600x450?text=No+Image'
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
            showTypeBadge={showTypeBadge}
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
        />
    );
}
