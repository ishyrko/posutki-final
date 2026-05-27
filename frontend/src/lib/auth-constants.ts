export const AUTH_TOKEN_KEY = 'token';
export const AUTH_COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 30;

/** Страница подачи объявления — middleware проверяет cookie, не localStorage. */
export const LISTING_SUBMIT_PATH = '/razmestit/';

/** Безопасный in-app путь из `?next=` (open redirect исключён). */
export function resolveAuthRedirectPath(next: string | null | undefined): string | undefined {
    if (!next || !next.startsWith('/')) {
        return undefined;
    }
    if (next.startsWith('//')) {
        return undefined;
    }
    return next;
}
