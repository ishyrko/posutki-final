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
    /** Current catalog band for this VIP level in the scope (1-based). */
    catalogPositionFrom?: number | null;
    catalogPositionTo?: number | null;
    /** Published listings currently at this effective VIP level. */
    catalogListingsAtLevel?: number | null;
}

/** Catalog band for free (effective level 0) listings in the same scope. */
export interface PlacementFreeTierCatalogBand {
    catalogPositionFrom: number | null;
    catalogPositionTo: number | null;
    catalogListingsAtLevel: number | null;
}

export interface PlacementLevelsResponse {
    levels: PlacementLevelPrice[];
    freeTier: PlacementFreeTierCatalogBand;
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

/** Max subscription horizon from today (renewal cannot push expiry past this). */
export const PLACEMENT_MAX_HORIZON_MONTHS = 12;

/**
 * How many months can still be added on renewal before hitting the 12‑month-from-today cap.
 * 0 → продление недоступно (текущий VIP уже дальше чем на 11 мес.).
 */
export function renewalMonthsAvailable(
    expiresAt: string | null | undefined,
    now: Date = new Date(),
): number {
    if (!expiresAt) {
        return PLACEMENT_MAX_HORIZON_MONTHS;
    }
    const expires = new Date(expiresAt);
    if (Number.isNaN(expires.getTime()) || expires <= now) {
        return PLACEMENT_MAX_HORIZON_MONTHS;
    }

    const cap = new Date(now);
    cap.setMonth(cap.getMonth() + PLACEMENT_MAX_HORIZON_MONTHS);
    if (expires >= cap) {
        return 0;
    }

    let months = 0;
    const cursor = new Date(expires);
    while (months < PLACEMENT_MAX_HORIZON_MONTHS) {
        cursor.setMonth(cursor.getMonth() + 1);
        if (cursor > cap) {
            break;
        }
        months += 1;
    }

    return months;
}

export function canRenewPlacementLevel(
    expiresAt: string | null | undefined,
    now: Date = new Date(),
): boolean {
    return renewalMonthsAvailable(expiresAt, now) >= 1;
}

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

    // «Бесплатный» только пока VIP 1 ещё от разовой выдачи (не было платного продления/апгрейда).
    if (property.placementIsTrial && baseLevel === 1) {
        const freeVip1Ends = property.freeTrialEndsAt
            ? new Date(property.freeTrialEndsAt).toLocaleDateString('ru-RU')
            : levelExpires;
        return freeVip1Ends
            ? `Бесплатный VIP 1 до ${freeVip1Ends}`
            : 'Бесплатный VIP 1';
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

/** Public gallery size for free placement; paid VIP and free VIP 1 show all photos. */
export const MAX_VISIBLE_PHOTOS_FREE_PLACEMENT = 5;

/** Tariffs page anchor describing free-tier limits (photos, video, Instagram, website). */
export const FREE_PLACEMENT_LIMITS_HREF = '/tarify/#besplatnoe';

export function isPlacementBoostActive(placementBoostExpiresAt?: string | null): boolean {
    if (!placementBoostExpiresAt) {
        return false;
    }
    return new Date(placementBoostExpiresAt) > new Date();
}

/** Human-readable catalog band: random rotation every 5 minutes within positions. */
export function formatCatalogPositionRange(
    level: {
        catalogPositionFrom?: number | null;
        catalogPositionTo?: number | null;
        catalogListingsAtLevel?: number | null;
    },
    locationLabel: string,
): string | null {
    const from = level.catalogPositionFrom;
    const to = level.catalogPositionTo;
    if (from == null || to == null || from < 1 || to < from) {
        return null;
    }

    const places =
        from === to ? `позиция ${from}` : `позиции от ${from} до ${to}`;
    const emptyHint =
        (level.catalogListingsAtLevel ?? 0) === 0 ? 'сейчас пусто, займёте ' : '';

    return `На текущий момент: ${emptyHint}${places} (${locationLabel}) · ротация каждые 5 мин.`;
}

/**
 * Adjust a catalog band for a listing that is about to join this level
 * (buy / boost). If it is already at the level, the live band already includes it.
 */
export function withListingInCatalogBand(
    band: {
        catalogPositionFrom?: number | null;
        catalogPositionTo?: number | null;
        catalogListingsAtLevel?: number | null;
    },
    alreadyAtLevel: boolean,
): {
    catalogPositionFrom: number | null;
    catalogPositionTo: number | null;
    catalogListingsAtLevel: number | null;
} {
    const from = band.catalogPositionFrom ?? null;
    const to = band.catalogPositionTo ?? null;
    const count = band.catalogListingsAtLevel ?? null;

    if (alreadyAtLevel || from == null || to == null) {
        return {
            catalogPositionFrom: from,
            catalogPositionTo: to,
            catalogListingsAtLevel: count,
        };
    }

    if ((count ?? 0) === 0) {
        // Empty level: API already gives the single prospective slot.
        return {
            catalogPositionFrom: from,
            catalogPositionTo: to,
            catalogListingsAtLevel: 1,
        };
    }

    return {
        catalogPositionFrom: from,
        catalogPositionTo: to + 1,
        catalogListingsAtLevel: (count ?? 0) + 1,
    };
}
