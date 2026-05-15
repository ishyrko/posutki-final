import { AUTH_COOKIE_MAX_AGE_SECONDS, AUTH_TOKEN_KEY } from '@/lib/auth-constants';

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

export const setToken = (token: string) => {
    if (!isBrowser) return;

    localStorage.setItem(AUTH_TOKEN_KEY, token);
    setAuthCookie(token);
    dispatchAuthChanged();
};

export const removeToken = () => {
    if (!isBrowser) return;

    localStorage.removeItem(AUTH_TOKEN_KEY);
    removeAuthCookie();
    dispatchAuthChanged();
};

export const isAuthenticated = () => !!getToken();

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
