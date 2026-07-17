'use client';

import { useSyncExternalStore } from 'react';

/** true только после гидрации — безопасно для localStorage и auth в SSR. */
export function useIsHydrated(): boolean {
    return useSyncExternalStore(
        () => () => {},
        () => true,
        () => false,
    );
}
