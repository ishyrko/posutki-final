'use client';

import { createContext, useContext, type ReactNode } from 'react';

type OwnerFeaturesContextValue = {
    initialHasMyProperties: boolean;
};

const OwnerFeaturesContext = createContext<OwnerFeaturesContextValue | null>(null);

export function OwnerFeaturesProvider({
    initialHasMyProperties,
    children,
}: {
    initialHasMyProperties: boolean;
    children: ReactNode;
}) {
    return (
        <OwnerFeaturesContext.Provider value={{ initialHasMyProperties }}>
            {children}
        </OwnerFeaturesContext.Provider>
    );
}

export function useOwnerFeaturesContext(): OwnerFeaturesContextValue | null {
    return useContext(OwnerFeaturesContext);
}
