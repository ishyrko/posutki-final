'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useConfirmResetPassword } from '../hooks';
import { confirmResetPasswordSchema, ConfirmResetPasswordData } from '../api';
import { motion } from 'framer-motion';
import Link from 'next/link';
import NextImage from 'next/image';
import { Lock, ArrowLeft, ArrowRight, AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    email: string;
    token: string;
}

export function ConfirmResetPasswordForm({ email, token }: Props) {
    const { mutate: confirm, isPending } = useConfirmResetPassword();

    const form = useForm<ConfirmResetPasswordData>({
        resolver: zodResolver(confirmResetPasswordSchema),
        defaultValues: {
            email,
            token,
            password: '',
            passwordConfirm: '',
        },
    });

    if (!email || !token) {
        return (
            <div className="min-h-screen bg-dark-bg flex items-center justify-center px-6 py-12">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5 }}
                    className="w-full max-w-md text-center"
                >
                    <div className="w-16 h-16 rounded-full bg-destructive/10 flex items-center justify-center mx-auto mb-6">
                        <AlertTriangle className="w-8 h-8 text-destructive" />
                    </div>
                    <h2 className="text-3xl font-display font-bold text-dark-fg mb-3">
                        Недействительная ссылка
                    </h2>
                    <p className="text-dark-muted mb-8">
                        Ссылка для сброса пароля недействительна или просрочена. Запросите новую.
                    </p>
                    <Link href="/reset-password/">
                        <Button className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0 h-11">
                            Запросить новую ссылку
                        </Button>
                    </Link>
                </motion.div>
            </div>
        );
    }

    const onSubmit = (data: ConfirmResetPasswordData) => {
        confirm({
            email: data.email,
            token: data.token,
            password: data.password,
        });
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

                <h2 className="text-3xl font-display font-bold text-dark-fg mb-2">
                    Новый пароль
                </h2>
                <p className="text-dark-muted mb-8">
                    Придумайте новый пароль для вашего аккаунта.
                </p>

                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5">
                    <div className="space-y-2">
                        <Label className="text-dark-fg/80">Новый пароль</Label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-muted" />
                            <Input
                                type="password"
                                placeholder="Минимум 6 символов"
                                className="pl-10 bg-dark-card border-dark-card text-dark-fg placeholder:text-dark-muted/60 focus-visible:ring-primary"
                                {...form.register('password')}
                            />
                            {form.formState.errors.password && (
                                <p className="text-destructive text-xs mt-1">
                                    {form.formState.errors.password.message}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label className="text-dark-fg/80">Подтвердите пароль</Label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-muted" />
                            <Input
                                type="password"
                                placeholder="Повторите пароль"
                                className="pl-10 bg-dark-card border-dark-card text-dark-fg placeholder:text-dark-muted/60 focus-visible:ring-primary"
                                {...form.register('passwordConfirm')}
                            />
                            {form.formState.errors.passwordConfirm && (
                                <p className="text-destructive text-xs mt-1">
                                    {form.formState.errors.passwordConfirm.message}
                                </p>
                            )}
                        </div>
                    </div>

                    <Button
                        type="submit"
                        disabled={isPending}
                        className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0 h-11"
                    >
                        {isPending ? 'Сохранение...' : 'Сохранить пароль'}
                        {!isPending && <ArrowRight className="w-4 h-4 ml-2" />}
                    </Button>
                </form>

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
