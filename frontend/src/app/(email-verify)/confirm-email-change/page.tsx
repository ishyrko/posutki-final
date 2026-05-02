'use client';

import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { Suspense, useEffect, useState } from 'react';
import { confirmEmailChange } from '@/features/auth/api';
import NextImage from 'next/image';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { useQueryClient } from '@tanstack/react-query';

function ConfirmEmailChangeContent() {
    const router = useRouter();
    const queryClient = useQueryClient();
    const searchParams = useSearchParams();
    const token = searchParams.get('token') || '';
    const email = searchParams.get('email') || '';
    const [status, setStatus] = useState<'loading' | 'error'>('loading');

    useEffect(() => {
        if (!token || !email) {
            return;
        }

        let cancelled = false;
        (async () => {
            try {
                await confirmEmailChange({ email, token });
                if (!cancelled) {
                    toast.success('Новый email подтверждён.');
                    await queryClient.invalidateQueries({ queryKey: ['me'] });
                    router.replace('/kabinet/profil');
                }
            } catch {
                if (!cancelled) {
                    setStatus('error');
                    toast.error('Ссылка недействительна или срок её действия истёк.');
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [token, email, router, queryClient]);

    if (!token || !email) {
        return (
            <div className="min-h-screen bg-dark-bg flex flex-col items-center justify-center px-6">
                <p className="text-dark-muted mb-4">Неверная ссылка подтверждения.</p>
                <Link href="/kabinet/profil" className="text-primary hover:underline">
                    В профиль
                </Link>
            </div>
        );
    }

    if (status === 'loading') {
        return (
            <div className="min-h-screen bg-dark-bg flex flex-col items-center justify-center gap-4">
                <Link href="/" className="mb-4">
                    <NextImage
                        src="/rnb-logo-transparent.png"
                        alt="RNB.by"
                        width={600}
                        height={207}
                        className="h-10 w-auto object-contain"
                    />
                </Link>
                <Loader2 className="w-10 h-10 animate-spin text-primary" />
                <p className="text-dark-muted text-sm">Подтверждаем новый email…</p>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-dark-bg flex flex-col items-center justify-center px-6">
            <p className="text-destructive mb-4">Не удалось подтвердить email.</p>
            <Link href="/kabinet/profil" className="text-primary hover:underline">
                Вернуться в профиль
            </Link>
        </div>
    );
}

export default function ConfirmEmailChangePage() {
    return (
        <Suspense fallback={<div className="min-h-screen bg-dark-bg" />}>
            <ConfirmEmailChangeContent />
        </Suspense>
    );
}
