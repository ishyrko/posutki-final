'use client';

import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { Suspense, useState } from 'react';
import { resendVerification } from '@/features/auth/api';
import { motion } from 'framer-motion';
import NextImage from 'next/image';
import { Mail, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';

function VerifyEmailPendingContent() {
    const searchParams = useSearchParams();
    const email = searchParams.get('email') || '';
    const [isSending, setIsSending] = useState(false);

    const handleResend = async () => {
        if (!email) {
            toast.error('Не указан email');
            return;
        }
        setIsSending(true);
        try {
            await resendVerification(email);
            toast.success('Если аккаунт существует, мы отправили письмо.');
        } catch {
            toast.error('Не удалось отправить письмо');
        } finally {
            setIsSending(false);
        }
    };

    return (
        <div className="min-h-screen bg-dark-bg flex items-center justify-center px-6 py-12">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="w-full max-w-md text-center"
            >
                <Link href="/" className="inline-flex items-center justify-center mb-10">
                    <NextImage
                        src="/rnb-logo-transparent.png"
                        alt="RNB.by"
                        width={600}
                        height={207}
                        className="h-10 w-auto object-contain"
                        priority
                    />
                </Link>
                <div className="rounded-xl border border-dark-card bg-dark-card/40 p-8">
                    <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/15 text-primary">
                        <Mail className="h-7 w-7" />
                    </div>
                    <h1 className="text-2xl font-display font-bold text-dark-fg mb-2">Проверьте почту</h1>
                    <p className="text-dark-muted text-sm mb-6">
                        Мы отправили письмо со ссылкой для подтверждения на{' '}
                        {email ? <span className="text-dark-fg font-medium">{email}</span> : 'указанный адрес'}.
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        className="w-full border-dark-card mb-4"
                        onClick={handleResend}
                        disabled={isSending || !email}
                    >
                        {isSending ? 'Отправка...' : 'Отправить письмо ещё раз'}
                    </Button>
                    <Link
                        href="/login"
                        className="inline-flex items-center justify-center gap-2 text-primary text-sm font-medium hover:underline"
                    >
                        Перейти ко входу
                        <ArrowRight className="w-4 h-4" />
                    </Link>
                </div>
            </motion.div>
        </div>
    );
}

export default function VerifyEmailPendingPage() {
    return (
        <Suspense fallback={<div className="min-h-screen bg-dark-bg" />}>
            <VerifyEmailPendingContent />
        </Suspense>
    );
}
