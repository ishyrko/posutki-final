import { AUTH_COOKIE_MAX_AGE_SECONDS, AUTH_TOKEN_KEY } from '@/lib/auth-constants';

const isBrowser = typeof window !== 'undefined';

const hasAuthCookie = () =>
    isBrowser &&
    document.cookie
        .split('; ')
        .some((cookie) => cookie.startsWith(`${AUTH_TOKEN_KEY}=`));

const setAuthCookie = (token: string) => {
    if (!isBrowser) return;

    document.cookie = `${AUTH_TOKEN_KEY}=${encodeURIComponent(token)}; path=/; max-age=${AUTH_COOKIE_MAX_AGE_SECONDS}; samesite=lax`;
};

const removeAuthCookie = () => {
    if (!isBrowser) return;

    document.cookie = `${AUTH_TOKEN_KEY}=; path=/; max-age=0; samesite=lax`;
};

export const getToken = () => (isBrowser ? localStorage.getItem(AUTH_TOKEN_KEY) : null);

export const setToken = (token: string) => {
    if (!isBrowser) return;

    localStorage.setItem(AUTH_TOKEN_KEY, token);
    setAuthCookie(token);
};

export const removeToken = () => {
    if (!isBrowser) return;

    localStorage.removeItem(AUTH_TOKEN_KEY);
    removeAuthCookie();
};

export const isAuthenticated = () => {
    const token = getToken();

    // Keep auth cookie in sync for middleware redirects.
    if (token && !hasAuthCookie()) {
        setAuthCookie(token);
    }

    return !!token;
};

/** Safe in-app path from `?next=` (open redirects excluded). */
export function resolveAuthRedirectPath(next: string | null | undefined): string | undefined {
    if (!next || !next.startsWith('/')) {
        return undefined;
    }
    if (next.startsWith('//')) {
        return undefined;
    }
    return next;
}
