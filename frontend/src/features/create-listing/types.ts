export interface City {
    id: number;
    name: string;
    slug: string;
    region: string;
}

export interface CitySearchResult {
    id: number;
    name: string;
    slug: string;
    shortName: string;
    districtName?: string;
    regionName?: string;
    ruralCouncil?: string;
    latitude?: number;
    longitude?: number;
}

export interface Street {
    id: number;
    name: string;
    slug: string;
    type: string | null;
}

export interface CreatePropertyPayload {
    type: string;
    dealType: string;
    title: string;
    description: string;
    price: { amount: number; currency: string };
    area: number;
    landArea?: number;
    rooms?: number;
    roomsInDeal?: number;
    roomsArea?: number;
    floor?: number;
    totalFloors?: number;
    bathrooms?: number;
    yearBuilt?: number;
    renovation?: string;
    balcony?: string;
    livingArea?: number;
    kitchenArea?: number;
    dealConditions?: string[];
    maxDailyGuests?: number;
    dailySingleBeds?: number;
    dailyDoubleBeds?: number;
    checkInTime?: string;
    checkOutTime?: string;
    building: string;
    block?: string;
    cityId: number;
    streetId?: number | null;
    coordinates: { latitude: number; longitude: number };
    images: string[];
    amenities: string[];
}

export interface CreatePropertyResponse {
    message: string;
    propertyId: number;
}

export interface UploadResponse {
    url: string;
    thumbnailUrl?: string | null;
}

export interface UploadedPhoto {
    url: string;
    file?: File;
    uploading?: boolean;
}

export interface ListingFormData {
    dealType: string;
    propertyCategory: string;
    propertyType: string;
    title: string;
    description: string;
    rooms: string;
    roomsInDeal: string;
    roomsArea: string;
    bathrooms: string;
    area: string;
    landArea: string;
    livingArea: string;
    kitchenArea: string;
    floor: string;
    totalFloors: string;
    maxDailyGuests: string;
    dailySingleBeds: string;
    dailyDoubleBeds: string;
    checkInTime: string;
    checkOutTime: string;
    yearBuilt: string;
    renovation: string;
    balcony: string;
    dealConditions: string[];
    photos: UploadedPhoto[];
    cityId: number | null;
    citySlug: string;
    cityName: string;
    streetName: string;
    streetId: number | null;
    building: string;
    block: string;
    latitude: number | null;
    longitude: number | null;
    price: string;
    currency: string;
    amenities: string[];
}
