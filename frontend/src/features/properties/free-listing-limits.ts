import type { FreeListingLimitsInfo, Property } from '@/features/properties/types';

const LIMIT_ACTION_HINT =
    ' Оплатите VIP или освободите слот: скройте другое объявление или купите для него VIP.';

export function formatFreeLimitExceededIntro(
    property: Property,
    accountLimits?: Pick<FreeListingLimitsInfo['account'], 'used' | 'limit'>,
): string {
    if (!accountLimits) {
        return 'Ваш лимит бесплатных объявлений исчерпан';
    }

    if (accountLimits.used >= accountLimits.limit) {
        return 'Ваш лимит бесплатных объявлений на аккаунте исчерпан';
    }

    if (property.type === 'apartment' && property.address.cityName) {
        return `Ваш лимит бесплатных объявлений для города ${property.address.cityName} исчерпан`;
    }

    return 'Ваш лимит бесплатных объявлений исчерпан';
}

export function formatFreeLimitExceededMessage(
    property: Property,
    accountLimits?: Pick<FreeListingLimitsInfo['account'], 'used' | 'limit'>,
): string {
    return `${formatFreeLimitExceededIntro(property, accountLimits)}.${LIMIT_ACTION_HINT}`;
}
