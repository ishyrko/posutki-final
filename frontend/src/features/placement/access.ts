/** Покупка VIP/буста и страница тарифов — только для тестового аккаунта. */
export const PLACEMENT_COMMERCE_USER_ID = 1;

export function canAccessPlacementCommerce(userId: number | null | undefined): boolean {
    return userId === PLACEMENT_COMMERCE_USER_ID;
}
