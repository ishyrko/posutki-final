'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useResetPassword } from '../hooks';
import { resetPasswordSchema, ResetPasswordData } from '../api';
import { motion } from 'framer-motion';
import Link from 'next/link';
import NextImage from 'next/image';
import { Mail, ArrowLeft, ArrowRight, CheckCircle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function ResetPasswordForm() {
    const { mutate: sendResetLink, isPending, isSuccess } = useResetPassword();
    const [submittedEmail, setSubmittedEmail] = useState('');

    const form = useForm<ResetPasswordData>({
        resolver: zodResolver(resetPasswordSchema),
        defaultValues: {
            email: '',
        },
    });

    const onSubmit = (data: ResetPasswordData) => {
        setSubmittedEmail(data.email);
        sendResetLink(data);
    };

    return (
        <div className="min-h-screen bg-dark-bg flex items-center justify-center px-6 py-12">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
                className="w-full max-w-md"
            >
                <Link href="/" className="inline-flex items-center mb-10">
                    <NextImage
                        src="/rnb-logo-transparent.png"
                        alt="RNB.by"
                        width={600}
                        height={207}
                        className="h-10 w-auto object-contain"
                        priority
                    />
                </Link>

                {!isSuccess ? (
                    <>
                        <h2 className="text-3xl font-display font-bold text-dark-fg mb-2">
                            Восстановление пароля
                        </h2>
                        <p className="text-dark-muted mb-8">
                            Введите email, указанный при регистрации. Мы отправим ссылку для сброса пароля.
                        </p>

                        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5">
                            <div className="space-y-2">
                                <Label className="text-dark-fg/80">Email</Label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-muted" />
                                    <Input
                                        type="email"
                                        placeholder="Например: you@example.com"
                                        className="pl-10 bg-dark-card border-dark-card text-dark-fg placeholder:text-dark-muted/60 focus-visible:ring-primary"
                                        {...form.register('email')}
                                    />
                                    {form.formState.errors.email && (
                                        <p className="text-destructive text-xs mt-1">
                                            {form.formState.errors.email.message}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <Button
                                type="submit"
                                disabled={isPending}
                                className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0 h-11"
                            >
                                {isPending ? 'Отправка...' : 'Отправить ссылку'}
                                {!isPending && <ArrowRight className="w-4 h-4 ml-2" />}
                            </Button>
                        </form>
                    </>
                ) : (
                    <motion.div
                        initial={{ opacity: 0, scale: 0.95 }}
                        animate={{ opacity: 1, scale: 1 }}
                        transition={{ duration: 0.3 }}
                        className="text-center"
                    >
                        <div className="w-16 h-16 rounded-full bg-green-500/10 flex items-center justify-center mx-auto mb-6">
                            <CheckCircle className="w-8 h-8 text-green-400" />
                        </div>
                        <h2 className="text-3xl font-display font-bold text-dark-fg mb-3">
                            Письмо отправлено
                        </h2>
                        <p className="text-dark-muted mb-8 leading-relaxed">
                            Мы отправили инструкции по сбросу пароля на{' '}
                            <span className="text-dark-fg font-medium">{submittedEmail}</span>.
                            {' '}Проверьте почту, включая папку «Спам».
                        </p>
                        <Button
                            onClick={() => onSubmit({ email: submittedEmail })}
                            disabled={isPending}
                            variant="outline"
                            className="bg-dark-card border-dark-card text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card/80"
                        >
                            {isPending ? 'Отправка...' : 'Отправить повторно'}
                        </Button>
                    </motion.div>
                )}

                <div className="mt-8 text-center">
                    <Link
                        href="/login/"
                        className="text-sm text-dark-muted hover:text-dark-fg transition-colors inline-flex items-center gap-1.5"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        Вернуться ко входу
                    </Link>
                </div>
            </motion.div>
        </div>
    );
}
