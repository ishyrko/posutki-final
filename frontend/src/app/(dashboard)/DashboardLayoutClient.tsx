'use client';

import { useEffect, useSyncExternalStore } from 'react';
import { useRouter } from 'next/navigation';
import { useUser } from '@/features/auth/hooks';
import { Sidebar } from '@/components/dashboard/Sidebar';
import Header from '@/components/Header';
import { isAuthenticated, removeToken } from '@/lib/auth';

function DashboardLoadingScreen() {
    return (
        <div className="flex h-screen items-center justify-center bg-background">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
        </div>
    );
}

function DashboardAuthenticatedShell({ children }: { children: React.ReactNode }) {
    const router = useRouter();
    const { isLoading, isError } = useUser();

    useEffect(() => {
        if (isError) {
            removeToken();
            router.replace('/login/');
        }
    }, [isError, router]);

    if (isLoading || isError) {
        return <DashboardLoadingScreen />;
    }

    return (
        <div className="min-h-screen flex flex-col bg-background">
            <Header />
            <div className="flex-1 container py-6 flex gap-6">
                <Sidebar />
                <main className="flex-1 min-w-0 pb-[calc(5rem+env(safe-area-inset-bottom,0px))] lg:pb-0">
                    {children}
                </main>
            </div>
        </div>
    );
}

export default function DashboardLayoutClient({
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

    useEffect(() => {
        if (!isMounted) return;
        if (!isAuth) {
            // Prevent redirect loops when auth cookie/token get out of sync.
            removeToken();
            router.replace('/login/');
        }
    }, [isMounted, isAuth, router]);

    // Keep first server/client render identical to avoid hydration mismatches.
    // useUser() runs only after mount and auth check, when QueryClient is available.
    if (!isMounted || !isAuth) {
        return <DashboardLoadingScreen />;
    }

    return <DashboardAuthenticatedShell>{children}</DashboardAuthenticatedShell>;
}
