export type PlacementPurchaseKind = 'level' | 'boost';

export type PlacementPurchaseStatus =
    | 'pending_payment'
    | 'active'
    | 'expired'
    | 'cancelled'
    | 'rejected'
    | 'superseded';

export type PlacementPropertyType = 'apartment' | 'house';

export type PlacementTariffScope =
    | { propertyType: 'apartment'; cityId: number }
    | { propertyType: 'house'; regionId: number };

export interface PlacementLevelPrice {
    id: number;
    propertyType: PlacementPropertyType;
    cityId: number | null;
    regionId: number | null;
    level: number;
    label: string;
    capacity: number | null;
    occupied: number;
    available: number | null;
    priceBynPerMonth: number;
}

export interface PlacementScopeSettings {
    propertyType: PlacementPropertyType;
    cityId?: number | null;
    regionId?: number | null;
    maxLevel: number;
    boostPriceByn: number | null;
}

export interface PlacementPurchase {
    id: number;
    propertyId: number;
    propertyTitle?: string | null;
    kind: PlacementPurchaseKind;
    kindLabel: string;
    level: number | null;
    levelPriceId: number | null;
    durationMonths: number | null;
    priceByn: number;
    status: PlacementPurchaseStatus;
    statusLabel: string;
    source: string;
    createdAt: string;
    activatedAt?: string | null;
    expiresAt?: string | null;
    reservationExpiresAt?: string | null;
    note?: string | null;
    basePurchaseId?: number | null;
}

export interface PlacementPurchaseQuote {
    mode: 'new' | 'renewal' | 'upgrade';
    priceByn: number;
    currentLevel: number | null;
    currentExpiresAt: string | null;
    targetExpiresAt: string | null;
}

export const PLACEMENT_DURATIONS = [1, 3, 6, 12] as const;

export function isPlacementPurchasePayable(purchase: PlacementPurchase): boolean {
    if (purchase.status !== 'pending_payment') {
        return false;
    }
    if (!purchase.reservationExpiresAt) {
        return false;
    }
    return new Date(purchase.reservationExpiresAt) > new Date();
}

export function formatPlacementStatus(property: {
    placementBaseLevel?: number | null;
    placementEffectiveLevel?: number | null;
    placementLevelExpiresAt?: string | null;
    placementBoostExpiresAt?: string | null;
    placementIsTrial?: boolean;
    freeTrialEndsAt?: string | null;
}): string {
    const baseLevel = property.placementBaseLevel ?? 0;
    const effectiveLevel = property.placementEffectiveLevel ?? baseLevel;
    const levelExpires = property.placementLevelExpiresAt
        ? new Date(property.placementLevelExpiresAt).toLocaleString('ru-RU', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : null;
    const boostExpires = property.placementBoostExpiresAt
        ? new Date(property.placementBoostExpiresAt).toLocaleString('ru-RU', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : null;

    if (property.placementIsTrial && baseLevel === 1) {
        const trialEnds = property.freeTrialEndsAt
            ? new Date(property.freeTrialEndsAt).toLocaleDateString('ru-RU')
            : levelExpires;
        return trialEnds
            ? `VIP 1 (бесплатный пробный период до ${trialEnds})`
            : 'VIP 1 (бесплатный пробный период)';
    }

    if (baseLevel <= 0) {
        const boostPart = boostExpires ? `, буст до ${boostExpires}` : '';
        return boostPart ? `Бесплатное размещение${boostPart}` : 'Бесплатное размещение';
    }

    const basePart = levelExpires
        ? `VIP ${baseLevel}, до ${levelExpires}`
        : `VIP ${baseLevel}`;

    if (effectiveLevel > baseLevel) {
        return boostExpires
            ? `${basePart} · эффективно VIP ${effectiveLevel} (буст до ${boostExpires})`
            : `${basePart} · эффективно VIP ${effectiveLevel}`;
    }

    return basePart;
}

export function isPlacementBoostActive(placementBoostExpiresAt?: string | null): boolean {
    if (!placementBoostExpiresAt) {
        return false;
    }
    return new Date(placementBoostExpiresAt) > new Date();
}
