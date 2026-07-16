export type PlacementType = 'special' | 'standard' | 'free';

export type PlacementPurchaseType = 'special' | 'standard';

export type PlacementPurchaseStatus =
    | 'pending_payment'
    | 'active'
    | 'expired'
    | 'cancelled'
    | 'rejected';

export type PlacementPropertyType = 'apartment' | 'house';

export type PlacementTariffScope =
    | { propertyType: 'apartment'; cityId: number }
    | { propertyType: 'house'; regionId: number };

export interface PlacementSlot {
    id: number;
    propertyType: PlacementPropertyType;
    cityId: number | null;
    regionId: number | null;
    rankFrom: number;
    rankTo: number;
    label: string;
    capacity: number;
    occupied: number;
    available: number;
    priceBynPerMonth: number;
}

export interface StandardPlacementPrice {
    propertyType: PlacementPropertyType;
    cityId?: number | null;
    regionId?: number | null;
    priceBynPerMonth: number;
}

export interface PlacementPurchase {
    id: number;
    propertyId: number;
    propertyTitle?: string | null;
    type: PlacementPurchaseType;
    typeLabel: string;
    slotId: number | null;
    slotLabel: string | null;
    durationMonths: number;
    priceByn: number;
    status: PlacementPurchaseStatus;
    statusLabel: string;
    source: string;
    createdAt: string;
    activatedAt?: string | null;
    expiresAt?: string | null;
    reservationExpiresAt?: string | null;
    note?: string | null;
}

export const PLACEMENT_DURATIONS = [1, 3, 6, 12] as const;

export function formatPlacementStatus(property: {
    placementType?: PlacementType | string | null;
    placementSlotRank?: number | null;
    placementExpiresAt?: string | null;
    placementIsTrial?: boolean;
    freeTrialEndsAt?: string | null;
}): string {
    const type = property.placementType ?? 'free';
    const expires = property.placementExpiresAt
        ? new Date(property.placementExpiresAt).toLocaleDateString('ru-RU')
        : null;

    if (type === 'special') {
        const rank = property.placementSlotRank != null ? `позиция от ${property.placementSlotRank}` : 'топ';
        return expires ? `Спецразмещение (${rank}), до ${expires}` : `Спецразмещение (${rank})`;
    }

    if (type === 'standard') {
        if (property.placementIsTrial) {
            const trialEnds = property.freeTrialEndsAt
                ? new Date(property.freeTrialEndsAt).toLocaleDateString('ru-RU')
                : expires;
            return trialEnds
                ? `Стандартное (бесплатный пробный период до ${trialEnds})`
                : 'Стандартное (бесплатный пробный период)';
        }
        return expires ? `Стандартное, оплачено до ${expires}` : 'Стандартное размещение';
    }

    return 'Бесплатное (с ограничениями)';
}

export function placementBadgeLabel(
    placementType?: string | null,
    placementSlotRank?: number | null,
): string | null {
    if (placementType !== 'special') {
        return null;
    }
    if (placementSlotRank == null) {
        return 'Топ';
    }
    return placementSlotRank === 1 ? 'Топ-1' : `Топ-${placementSlotRank}`;
}
