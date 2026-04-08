'use client';

import { useSyncExternalStore } from 'react';
import { isAuthenticated } from '@/lib/auth';

/**
 * Наличие токена в браузере без рассинхрона SSR/гидрации:
 * на сервере и при первом проходе гидрации — false, после монтирования — фактическое значение.
 */
export function useHasAuthToken(): boolean {
    return useSyncExternalStore(
        () => () => {},
        () => isAuthenticated(),
        () => false,
    );
}
