'use client';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useEffect, useState, ReactNode } from 'react';

const PERFORMANCE_MEASURE_ERROR_PATTERN = /(negative time stamp|does not exist)/i;

export default function QueryProvider({ children }: { children: ReactNode }) {
    const [queryClient] = useState(
        () =>
            new QueryClient({
                defaultOptions: {
                    queries: {
                        // With SSR, we usually want to set some default staleTime
                        // above 0 to avoid refetching immediately on the client
                        staleTime: 60 * 1000,
                        retry: 1,
                        refetchOnWindowFocus: false,
                    },
                },
            })
    );

    useEffect(() => {
        if (process.env.NODE_ENV !== 'development') {
            return;
        }

        if (typeof window === 'undefined' || typeof window.performance?.measure !== 'function') {
            return;
        }

        const originalMeasure = window.performance.measure.bind(window.performance);

        window.performance.measure = ((...args: Parameters<Performance['measure']>) => {
            try {
                return originalMeasure(...args);
            } catch (error) {
                if (PERFORMANCE_MEASURE_ERROR_PATTERN.test(String(error && (error as Error).message ? (error as Error).message : error))) {
                    return undefined as unknown as ReturnType<Performance['measure']>;
                }
                throw error;
            }
        }) as Performance['measure'];

        return () => {
            window.performance.measure = originalMeasure as Performance['measure'];
        };
    }, []);

    return (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );
}
