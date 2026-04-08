'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Loader2 } from 'lucide-react';
import { isAuthenticated } from '@/lib/auth';

export default function AuthLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const router = useRouter();
    const shouldRedirect = isAuthenticated();

    useEffect(() => {
        if (shouldRedirect) {
            router.replace('/kabinet');
        }
    }, [router, shouldRedirect]);

    if (shouldRedirect) {
        return (
            <div className="flex items-center justify-center py-24">
                <Loader2 className="w-8 h-8 animate-spin text-primary" />
            </div>
        );
    }

    return <>{children}</>;
}
