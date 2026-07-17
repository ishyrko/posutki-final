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

export function formatPlacementPurchasePeriod(
    purchase: Pick<PlacementPurchase, 'kind' | 'durationMonths'>,
): string | null {
    if (purchase.kind === 'boost') {
        return '24 часа';
    }
    if (purchase.durationMonths == null) {
        return null;
    }
    if (purchase.durationMonths === 1) {
        return '1 месяц';
    }
    if (purchase.durationMonths < 5) {
        return `${purchase.durationMonths} месяца`;
    }
    return `${purchase.durationMonths} месяцев`;
}

export function formatPlacementPurchaseSummary(
    purchase: Pick<PlacementPurchase, 'kind' | 'kindLabel' | 'level' | 'durationMonths'>,
): string {
    let summary = purchase.kindLabel;
    if (purchase.level != null) {
        summary += ` (VIP ${purchase.level})`;
    }
    const period = formatPlacementPurchasePeriod(purchase);
    if (purchase.kind === 'level' && period != null) {
        summary += `, ${period}`;
    }
    return summary;
}

/** 2 × daily tariff gap between current and next VIP level (monthly / 30), rounded up. */
export function calcBoostPriceByn(
    currentLevel: number,
    levels: Array<{ level: number; priceBynPerMonth: number }>,
): number | null {
    const next = levels.find((item) => item.level === currentLevel + 1);
    if (!next) {
        return null;
    }

    let currentPrice = 0;
    if (currentLevel > 0) {
        const current = levels.find((item) => item.level === currentLevel);
        if (!current) {
            return null;
        }
        currentPrice = current.priceBynPerMonth;
    }

    return Math.max(0, Math.ceil((2 * (next.priceBynPerMonth - currentPrice)) / 30));
}

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

export function placementBadgeLabel(placementEffectiveLevel?: number | null): string | null {
    const level = placementEffectiveLevel ?? 0;
    if (level <= 0) {
        return null;
    }
    return `VIP ${level}`;
}

/** User-facing label for a placement level (free tier has no VIP number). */
export function placementLevelLabel(level: number): string {
    if (level <= 0) {
        return 'Бесплатно';
    }
    return `VIP ${level}`;
}

/** Public gallery size for free placement; VIP and trial show all photos. */
export const MAX_VISIBLE_PHOTOS_FREE_PLACEMENT = 5;

/** Tariffs page anchor describing free-tier limits (photos, video, Instagram, website). */
export const FREE_PLACEMENT_LIMITS_HREF = '/tarify/#besplatnoe';

export function isPlacementBoostActive(placementBoostExpiresAt?: string | null): boolean {
    if (!placementBoostExpiresAt) {
        return false;
    }
    return new Date(placementBoostExpiresAt) > new Date();
}
