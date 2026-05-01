import type { NearbyMetroStation } from "@/features/metro/types";

export interface PropertyImage {
    id: number;
    url: string;
    thumbnailUrl?: string | null;
}

export interface Address {
    regionName?: string;
    districtName?: string;
    cityId: number;
    cityName: string;
    citySlug?: string;
    streetId?: number | null;
    streetName?: string | null;
    building?: string;
    block?: string | null;
}

export interface Coordinates {
    latitude: number;
    longitude: number;
}

export interface Price {
    amount: number;
    currency: string;
}

export interface Property {
    id: number;
    ownerId?: number;
    contact?: {
        phone?: string | null;
        name?: string | null;
    };
    title: string;
    description: string;
    type: string;
    typeLabel?: string;
    dealType: 'sale' | 'rent' | 'daily';
    status: 'draft' | 'moderation' | 'rejected' | 'published' | 'archived' | 'deleted';
    moderationComment?: string | null;
    pendingRevisionStatus?: 'pending' | 'rejected' | null;
    pendingRevisionComment?: string | null;
    pendingRevisionData?: {
        type?: string;
        dealType?: string;
        title?: string;
        description?: string;
        priceAmount?: number;
        priceCurrency?: string;
        area?: number;
        landArea?: number;
        rooms?: number;
        floor?: number;
        totalFloors?: number;
        bathrooms?: number;
        yearBuilt?: number;
        renovation?: string;
        balcony?: string;
        livingArea?: number;
        kitchenArea?: number;
        roomsInDeal?: number;
        roomsArea?: number;
        dealConditions?: string[];
        maxDailyGuests?: number;
        dailyBedCount?: number;
        checkInTime?: string;
        checkOutTime?: string;
        building?: string;
        block?: string;
        cityId?: number;
        streetId?: number | null;
        latitude?: number;
        longitude?: number;
        images?: string[];
        amenities?: string[];
        contactPhone?: string;
        contactName?: string;
    } | null;
    price: Price;
    /** BYN equivalent from backend (authoritative for display when set). */
    priceByn?: number | null;
    address: Address;
    coordinates?: Coordinates;
    specifications: {
        area: number;
        landArea?: number | null;
        rooms?: number | null;
        bathrooms?: number | null;
        yearBuilt?: number | null;
        floor?: number | null;
        totalFloors?: number | null;
        renovation?: string | null;
        balcony?: string | null;
        livingArea?: number | null;
        kitchenArea?: number | null;
        roomsInDeal?: number | null;
        roomsArea?: number | null;
        dealConditions?: string[];
        maxDailyGuests?: number | null;
        dailyBedCount?: number | null;
        checkInTime?: string | null;
        checkOutTime?: string | null;
    };
    images: PropertyImage[];
    /** Удобства (id строк), приходят с API на чтение и уходят при обновлении. */
    amenities?: string[];
    nearMetro?: boolean;
    nearbyMetroStations?: NearbyMetroStation[];
    views?: number;
    phoneViews?: number;
    favoritesCount?: number;
    createdAt: string;
    publishedAt?: string | null;
    boostedAt?: string | null;
}

export interface PropertyStatsPoint {
    date: string;
    views: number;
    phoneViews: number;
    favorites: number;
}

export interface PropertyStats {
    property: {
        id: number;
        title: string;
    };
    period: number;
    totals: {
        views: number;
        phoneViews: number;
        favorites: number;
    };
    daily: PropertyStatsPoint[];
}

export type PriceType = 'total' | 'perMeter';
export type Currency = 'BYN' | 'USD' | 'EUR';

export interface PropertyFilters {
    page?: number;
    limit?: number;
    type?: string;
    /** Несколько типов; в API уходит как `types=a,b`; приоритетнее одного `type` */
    types?: readonly string[];
    dealType?: 'sale' | 'rent';
    regionSlug?: string;
    citySlug?: string;
    cityId?: number;
    minPrice?: number;
    maxPrice?: number;
    priceType?: PriceType;
    currency?: Currency;
    rooms?: number;
    metroStationId?: number;
    nearMetro?: boolean;
    sortBy?: string;
    sortOrder?: 'ASC' | 'DESC';
}

export interface PropertyListResponse {
    data: Property[];
    meta: {
        total: number;
        page: number;
        limit: number;
    };
}

/**
 * Compose a human-readable address string from the address object.
 */
export function formatAddress(address: Address): string {
    const parts: string[] = [];
    if (address.streetName) parts.push(address.streetName);
    if (address.building && address.block) {
        parts.push(`${address.building}, корп. ${address.block}`);
    } else if (address.building) {
        parts.push(address.building);
    } else if (address.block) {
        parts.push(`корп. ${address.block}`);
    }
    if (address.cityName) parts.push(address.cityName);
    return parts.join(', ');
}
