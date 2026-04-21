'use client';

import { useEffect, useSyncExternalStore } from 'react';
import { useRouter } from 'next/navigation';
import { useUser } from '@/features/auth/hooks';
import { Sidebar } from '@/components/dashboard/Sidebar';
import Header from '@/components/Header';
import { isAuthenticated, removeToken } from '@/lib/auth';

export default function DashboardLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const router = useRouter();
    const isMounted = useSyncExternalStore(
        () => () => {},
        () => true,
        () => false,
    );
    const isAuth = isMounted ? isAuthenticated() : false;
    const { isLoading, isError } = useUser();

    useEffect(() => {
        if (!isMounted) return;
        if (!isAuth) {
            // Prevent redirect loops when auth cookie/token get out of sync.
            removeToken();
            router.replace('/login/');
        }
    }, [isMounted, isAuth, router]);

    useEffect(() => {
        if (!isMounted || !isAuth) return;
        if (isError) {
            removeToken();
            router.replace('/login/');
        }
    }, [isMounted, isAuth, isError, router]);

    // Keep first server/client render identical to avoid hydration mismatches.
    // Never return null while redirecting — blank screen was mistaken for a failed navigation.
    if (!isMounted || isLoading || !isAuth || isError) {
        return (
            <div className="flex h-screen items-center justify-center bg-background">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
            </div>
        );
    }

    return (
        <div className="min-h-screen flex flex-col bg-background">
            <Header />
            <div className="flex-1 container py-6 flex gap-6">
                <Sidebar />
                <main className="flex-1 min-w-0 pb-24 lg:pb-0">
                    {children}
                </main>
            </div>
        </div>
    );
}
