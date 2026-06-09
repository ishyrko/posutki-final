import {
    AUTH_COOKIE_MAX_AGE_SECONDS,
    AUTH_REFRESH_TOKEN_KEY,
    AUTH_TOKEN_KEY,
    LISTING_SUBMIT_PATH,
    resolveAuthRedirectPath,
} from '@/lib/auth-constants';
import { readRefreshToken, refreshAuthTokens } from '@/lib/auth-refresh';

export { resolveAuthRedirectPath };

const isBrowser = typeof window !== 'undefined';

const hasAuthCookie = () =>
    isBrowser &&
    document.cookie
        .split('; ')
        .some((cookie) => cookie.startsWith(`${AUTH_TOKEN_KEY}=`));

/** JWT из cookie (middleware и setToken пишут тот же ключ, не httpOnly). */
function readTokenFromCookie(): string | null {
    if (!isBrowser) {
        return null;
    }
    const prefix = `${AUTH_TOKEN_KEY}=`;
    const parts = document.cookie.split('; ');
    for (const part of parts) {
        if (!part.startsWith(prefix)) {
            continue;
        }
        const raw = part.slice(prefix.length);
        try {
            return decodeURIComponent(raw);
        } catch {
            return raw;
        }
    }
    return null;
}

const dispatchAuthChanged = () => {
    if (isBrowser) {
        window.dispatchEvent(new CustomEvent('auth-changed'));
    }
};

const setAuthCookie = (token: string) => {
    if (!isBrowser) return;

    document.cookie = `${AUTH_TOKEN_KEY}=${encodeURIComponent(token)}; path=/; max-age=${AUTH_COOKIE_MAX_AGE_SECONDS}; samesite=lax`;
};

const removeAuthCookie = () => {
    if (!isBrowser) return;

    document.cookie = `${AUTH_TOKEN_KEY}=; path=/; max-age=0; samesite=lax`;
};

export const getRefreshToken = (): string | null => readRefreshToken();

export const getToken = (): string | null => {
    if (!isBrowser) {
        return null;
    }
    const fromStorage = localStorage.getItem(AUTH_TOKEN_KEY);
    if (fromStorage) {
        if (!hasAuthCookie()) {
            setAuthCookie(fromStorage);
        }
        return fromStorage;
    }
    const fromCookie = readTokenFromCookie();
    if (fromCookie) {
        localStorage.setItem(AUTH_TOKEN_KEY, fromCookie);
        return fromCookie;
    }
    return null;
};

export const setToken = (token: string, refreshToken?: string | null) => {
    if (!isBrowser) return;

    localStorage.setItem(AUTH_TOKEN_KEY, token);
    setAuthCookie(token);
    if (refreshToken) {
        localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, refreshToken);
    }
    dispatchAuthChanged();
};

export const removeToken = () => {
    if (!isBrowser) return;

    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_REFRESH_TOKEN_KEY);
    removeAuthCookie();
    dispatchAuthChanged();
};

export const isAuthenticated = () => !!getToken();

export const hasRefreshToken = () => !!getRefreshToken();

/** Пытается восстановить access JWT по refresh-токену. */
export const bootstrapAuthSession = async (): Promise<boolean> => {
    if (!isBrowser || getToken()) {
        return !!getToken();
    }

    const refreshToken = getRefreshToken();
    if (!refreshToken) {
        return false;
    }

    try {
        const pair = await refreshAuthTokens(refreshToken);
        setToken(pair.token, pair.refreshToken);
        return true;
    } catch {
        removeToken();
        return false;
    }
};

/** Редирект на логин при истёкшей сессии на странице подачи объявления. */
export const redirectToLoginIfListingSessionExpired = (): void => {
    if (!isBrowser) {
        return;
    }

    const { pathname, search } = window.location;
    const isListingPage = pathname === '/razmestit' || pathname.startsWith('/razmestit/');
    if (!isListingPage) {
        return;
    }

    const next = encodeURIComponent(`${pathname}${search}` || LISTING_SUBMIT_PATH);
    window.location.assign(`/login?next=${next}`);
};

/** Синхронизирует JWT из localStorage в cookie для middleware (например /razmestit/). */
export const syncAuthCookie = (): void => {
    if (!isBrowser) {
        return;
    }
    const fromStorage = localStorage.getItem(AUTH_TOKEN_KEY);
    if (fromStorage && !hasAuthCookie()) {
        setAuthCookie(fromStorage);
    }
};

/** Полный переход после входа: cookie точно уходит в следующий запрос, сбрасывается кэш роутера. */
export const navigateAfterAuth = (target: string): void => {
    if (!isBrowser) {
        return;
    }
    syncAuthCookie();
    window.location.assign(target);
};
