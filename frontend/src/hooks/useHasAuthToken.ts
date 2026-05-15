'use client';

import { useSyncExternalStore } from 'react';
import { AUTH_TOKEN_KEY } from '@/lib/auth-constants';
import { isAuthenticated } from '@/lib/auth';

const isBrowser = typeof window !== 'undefined';

/**
 * Наличие токена в браузере без рассинхрона SSR/гидрации:
 * на сервере и при первом проходе гидрации — false, после монтирования — фактическое значение.
 */
export function useHasAuthToken(): boolean {
    return useSyncExternalStore(
        (onStoreChange) => {
            if (!isBrowser) {
                return () => {};
            }
            const onStorage = (e: StorageEvent) => {
                if (e.key === AUTH_TOKEN_KEY || e.key === null) {
                    onStoreChange();
                }
            };
            const onAuthChanged = () => onStoreChange();
            window.addEventListener('storage', onStorage);
            window.addEventListener('auth-changed', onAuthChanged);
            return () => {
                window.removeEventListener('storage', onStorage);
                window.removeEventListener('auth-changed', onAuthChanged);
            };
        },
        () => isAuthenticated(),
        () => false,
    );
}
