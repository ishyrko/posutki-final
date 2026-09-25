'use client';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useEffect, useState, ReactNode } from 'react';
import { syncAuthCookie } from '@/lib/auth';
import type { ExchangeRates } from '@/features/properties/api';

export default function QueryProvider({
    children,
    initialExchangeRates = null,
}: {
    children: ReactNode;
    initialExchangeRates?: ExchangeRates | null;
}) {
    const [queryClient] = useState(() => {
        const client = new QueryClient({
            defaultOptions: {
                queries: {
                    // With SSR, we usually want to set some default staleTime
                    // above 0 to avoid refetching immediately on the client
                    staleTime: 60 * 1000,
                    retry: 1,
                    refetchOnWindowFocus: false,
                },
            },
        });
        if (initialExchangeRates) {
            client.setQueryData(['exchange-rates'], initialExchangeRates);
        }
        return client;
    });

    useEffect(() => {
        syncAuthCookie();
    }, []);

    return (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );
}
