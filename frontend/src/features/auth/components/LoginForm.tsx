'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useLogin, useGoogleLogin } from '../hooks';
import { loginSchema, LoginCredentials } from '../api';
import { motion } from 'framer-motion';
import Link from 'next/link';
import NextImage from 'next/image';
import { Eye, EyeOff, Mail, Lock, ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { resolveAuthRedirectPath } from '@/lib/auth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { PhoneAuthModal } from '@/features/sms-auth/components/PhoneAuthModal';

export function LoginForm() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const redirectAfter = resolveAuthRedirectPath(searchParams.get('next'));
    const { mutate: login, isPending } = useLogin(redirectAfter);
    const { trigger: triggerGoogleLogin } = useGoogleLogin(redirectAfter);
    const [showPassword, setShowPassword] = useState(false);
    const [smsAuthOpen, setSmsAuthOpen] = useState(false);

    const form = useForm<LoginCredentials>({
        resolver: zodResolver(loginSchema),
        defaultValues: {
            email: '',
            password: '',
        },
    });

    const onSubmit = (data: LoginCredentials) => {
        login(data);
    };

    return (
        <div className="min-h-screen bg-dark-bg flex">
            {/* Left — branding */}
            <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center">
                <div className="absolute inset-0 bg-gradient-dark" />
                <div className="absolute inset-0 opacity-10">
                    <div className="absolute top-20 left-20 w-72 h-72 rounded-full bg-primary blur-[120px]" />
                    <div className="absolute bottom-20 right-20 w-96 h-96 rounded-full bg-primary blur-[160px]" />
                </div>
                <div className="relative z-10 px-16 max-w-lg">
                    <Link href="/" className="inline-flex items-center mb-12">
                        <NextImage
                            src="/rnb-logo-transparent.png"
                            alt="RNB.by"
                            width={600}
                            height={207}
                            className="h-12 w-auto object-contain"
                            priority
                        />
                    </Link>
                    <h1 className="text-4xl font-display font-bold text-dark-fg mb-4">
                        Найдите дом своей мечты
                    </h1>
                    <p className="text-dark-muted text-lg leading-relaxed">
                        Тысячи проверенных объявлений, удобный поиск и безопасные сделки — всё в одном месте.
                    </p>
                </div>
            </div>

            {/* Right — form */}
            <div className="flex-1 flex items-center justify-center px-6 py-12">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5 }}
                    className="w-full max-w-md"
                >
                    {/* Mobile logo */}
                    <Link href="/" className="lg:hidden inline-flex items-center mb-10">
                        <NextImage
                            src="/rnb-logo-transparent.png"
                            alt="RNB.by"
                            width={600}
                            height={207}
                            className="h-10 w-auto object-contain"
                            priority
                        />
                    </Link>

                    <h2 className="text-3xl font-display font-bold text-dark-fg mb-2">Вход в аккаунт</h2>
                    <p className="text-dark-muted mb-8">
                        Нет аккаунта?{" "}
                        <Link href="/register/" className="text-primary hover:underline font-medium">
                            Зарегистрируйтесь
                        </Link>
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
                                    <p className="text-destructive text-xs mt-1">{form.formState.errors.email.message}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label className="text-dark-fg/80">Пароль</Label>
                                <Link href="/reset-password/" className="text-xs text-primary hover:underline">
                                    Забыли пароль?
                                </Link>
                            </div>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-muted" />
                                <Input
                                    type={showPassword ? "text" : "password"}
                                    placeholder="••••••••"
                                    className="pl-10 pr-10 bg-dark-card border-dark-card text-dark-fg placeholder:text-dark-muted/60 focus-visible:ring-primary"
                                    {...form.register('password')}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-dark-muted hover:text-dark-fg transition-colors"
                                >
                                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                </button>
                                {form.formState.errors.password && (
                                    <p className="text-destructive text-xs mt-1">{form.formState.errors.password.message}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox id="remember" className="border-dark-muted data-[state=checked]:bg-primary data-[state=checked]:border-primary" />
                            <Label htmlFor="remember" className="text-sm text-dark-muted cursor-pointer">
                                Запомнить меня
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            disabled={isPending}
                            className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0 h-11"
                        >
                            {isPending ? 'Загрузка...' : 'Войти'}
                            {!isPending && <ArrowRight className="w-4 h-4 ml-2" />}
                        </Button>
                    </form>

                    <div className="mt-3">
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full border-dark-card bg-dark-card/50 text-dark-fg/80 hover:text-dark-fg hover:bg-dark-card"
                            onClick={() => setSmsAuthOpen(true)}
                        >
                            Войти по номеру телефона
                        </Button>
                    </div>

                    <div className="mt-8">
                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-dark-card" />
                            </div>
                            <div className="relative flex justify-center text-xs">
                                <span className="px-3 bg-dark-bg text-dark-muted">или войдите через</span>
                            </div>
                        </div>

                        <div className="mt-5">
                            <Button variant="outline" className="w-full border-dark-card bg-dark-card/50 text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card" onClick={triggerGoogleLogin} type="button">
                                <svg className="w-4 h-4 mr-2" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                Войти через Google
                            </Button>
                        </div>
                    </div>
                </motion.div>
            </div>
            <PhoneAuthModal
                open={smsAuthOpen}
                onOpenChange={setSmsAuthOpen}
                onSuccess={() => {
                    router.push(redirectAfter ?? '/kabinet');
                }}
            />
        </div>
    );
}
