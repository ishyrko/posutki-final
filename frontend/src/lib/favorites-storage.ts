export const FAVORITE_IDS_STORAGE_KEY = 'favorite_property_ids';

const FAVORITES_CHANGED_EVENT = 'favorites-changed';

const isBrowser = typeof window !== 'undefined';

const listeners = new Set<() => void>();

const dispatchFavoritesChanged = () => {
    if (!isBrowser) {
        return;
    }
    listeners.forEach((listener) => listener());
    window.dispatchEvent(new CustomEvent(FAVORITES_CHANGED_EVENT));
};

const EMPTY_FAVORITE_IDS: readonly number[] = [];

let cachedSnapshot: readonly number[] = EMPTY_FAVORITE_IDS;
let cachedRaw: string | null | undefined;

const parseFavoriteIds = (raw: string | null): number[] => {
    if (!raw) {
        return [];
    }

    try {
        const parsed: unknown = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [];
        }
        return parsed.filter((id): id is number => typeof id === 'number' && Number.isFinite(id) && id > 0);
    } catch {
        return [];
    }
};

const writeFavoriteIds = (ids: number[]): void => {
    if (!isBrowser) {
        return;
    }

    const serialized = JSON.stringify(ids);
    localStorage.setItem(FAVORITE_IDS_STORAGE_KEY, serialized);
    cachedRaw = serialized;
    cachedSnapshot = ids.length === 0 ? EMPTY_FAVORITE_IDS : ids;
    dispatchFavoritesChanged();
};

const readFavoriteIdsFromStorage = (): readonly number[] => {
    if (!isBrowser) {
        return EMPTY_FAVORITE_IDS;
    }

    const raw = localStorage.getItem(FAVORITE_IDS_STORAGE_KEY);
    if (raw === cachedRaw) {
        return cachedSnapshot;
    }

    cachedRaw = raw;
    const ids = parseFavoriteIds(raw);
    cachedSnapshot = ids.length === 0 ? EMPTY_FAVORITE_IDS : ids;
    return cachedSnapshot;
};

export const getLocalFavoriteIdsSnapshot = (): readonly number[] => readFavoriteIdsFromStorage();

export const getLocalFavoriteIdsServerSnapshot = (): readonly number[] => EMPTY_FAVORITE_IDS;

export const getLocalFavoriteIds = (): number[] => {
    const snapshot = readFavoriteIdsFromStorage();
    return snapshot.length === 0 ? [] : [...snapshot];
};

export const addLocalFavoriteId = (propertyId: number): void => {
    if (!isBrowser || propertyId <= 0) {
        return;
    }

    const ids = getLocalFavoriteIds();
    if (ids.includes(propertyId)) {
        return;
    }

    writeFavoriteIds([...ids, propertyId]);
};

export const removeLocalFavoriteId = (propertyId: number): void => {
    if (!isBrowser) {
        return;
    }

    writeFavoriteIds(getLocalFavoriteIds().filter((id) => id !== propertyId));
};

export const clearLocalFavoriteIds = (): void => {
    if (!isBrowser) {
        return;
    }

    localStorage.removeItem(FAVORITE_IDS_STORAGE_KEY);
    cachedRaw = null;
    cachedSnapshot = EMPTY_FAVORITE_IDS;
    dispatchFavoritesChanged();
};

export const subscribeLocalFavorites = (callback: () => void): (() => void) => {
    if (!isBrowser) {
        return () => {};
    }

    listeners.add(callback);

    const onStorage = (event: StorageEvent) => {
        if (event.key === FAVORITE_IDS_STORAGE_KEY || event.key === null) {
            cachedRaw = undefined;
            callback();
        }
    };

    const onCustomEvent = () => callback();

    window.addEventListener('storage', onStorage);
    window.addEventListener(FAVORITES_CHANGED_EVENT, onCustomEvent);

    return () => {
        listeners.delete(callback);
        window.removeEventListener('storage', onStorage);
        window.removeEventListener(FAVORITES_CHANGED_EVENT, onCustomEvent);
    };
};
