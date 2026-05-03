'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useLogin, useGoogleLogin } from '../hooks';
import { loginSchema, LoginCredentials } from '../api';
import Link from 'next/link';
import { Eye, EyeOff, Mail, Phone, ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { resolveAuthRedirectPath } from '@/lib/auth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { PhoneAuthPanel } from '@/features/sms-auth/components/PhoneAuthPanel';
import { GoogleMark } from './GoogleMark';

type LoginMethod = 'email' | 'phone';

export function LoginForm() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const redirectAfter = resolveAuthRedirectPath(searchParams.get('next'));
    const { mutate: login, isPending } = useLogin(redirectAfter);
    const { trigger: triggerGoogleLogin } = useGoogleLogin(redirectAfter);
    const [showPassword, setShowPassword] = useState(false);
    const [method, setMethod] = useState<LoginMethod>('email');

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

    const afterPhoneAuth = () => {
        router.push(redirectAfter ?? '/kabinet/');
    };

    return (
        <div className="min-h-screen bg-muted flex items-center justify-center p-4">
            <div className="w-full max-w-md">
                <Link
                    href="/"
                    className="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground mb-8 transition-colors"
                >
                    <ArrowLeft className="w-4 h-4" />
                    На главную
                </Link>

                <Card className="border-0 shadow-xl">
                    <CardContent className="p-8">
                        <div className="text-center mb-8">
                            <Link href="/" className="inline-block">
                                <span className="text-2xl font-bold font-display text-primary">posutki.by</span>
                            </Link>
                            <h1 className="text-xl font-semibold text-foreground mt-4">Вход в аккаунт</h1>
                            <p className="text-muted-foreground text-sm mt-1">
                                Войдите, чтобы управлять объявлениями и избранным
                            </p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            className="w-full h-12 text-sm font-medium gap-3"
                            onClick={triggerGoogleLogin}
                        >
                            <GoogleMark className="w-5 h-5" />
                            Войти через Google
                        </Button>

                        <div className="relative my-6">
                            <Separator />
                            <span className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-card px-3 text-xs text-muted-foreground">
                                или
                            </span>
                        </div>

                        <div className="flex gap-2 mb-6">
                            <Button
                                type="button"
                                variant={method === 'email' ? 'default' : 'outline'}
                                size="sm"
                                className="flex-1 gap-2"
                                onClick={() => setMethod('email')}
                            >
                                <Mail className="w-4 h-4" />
                                Email
                            </Button>
                            <Button
                                type="button"
                                variant={method === 'phone' ? 'default' : 'outline'}
                                size="sm"
                                className="flex-1 gap-2"
                                onClick={() => setMethod('phone')}
                            >
                                <Phone className="w-4 h-4" />
                                Телефон
                            </Button>
                        </div>

                        {method === 'email' ? (
                            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="login-email">Email</Label>
                                    <Input
                                        id="login-email"
                                        type="email"
                                        placeholder="your@email.com"
                                        className="h-11"
                                        {...form.register('email')}
                                    />
                                    {form.formState.errors.email && (
                                        <p className="text-destructive text-xs">{form.formState.errors.email.message}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between gap-2">
                                        <Label htmlFor="login-password">Пароль</Label>
                                        <Link href="/reset-password/" className="text-xs text-primary hover:underline shrink-0">
                                            Забыли пароль?
                                        </Link>
                                    </div>
                                    <div className="relative">
                                        <Input
                                            id="login-password"
                                            type={showPassword ? 'text' : 'password'}
                                            placeholder="••••••••"
                                            className="h-11 pr-10"
                                            {...form.register('password')}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                            aria-label={showPassword ? 'Скрыть пароль' : 'Показать пароль'}
                                        >
                                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                        </button>
                                    </div>
                                    {form.formState.errors.password && (
                                        <p className="text-destructive text-xs">{form.formState.errors.password.message}</p>
                                    )}
                                </div>
                                <Button type="submit" disabled={isPending} className="w-full h-11">
                                    {isPending ? 'Загрузка...' : 'Войти'}
                                </Button>
                            </form>
                        ) : (
                            <PhoneAuthPanel key="login-phone" onAuthenticated={afterPhoneAuth} />
                        )}

                        <p className="text-center text-sm text-muted-foreground mt-6">
                            Нет аккаунта?{' '}
                            <Link href="/register/" className="text-primary font-medium hover:underline">
                                Зарегистрироваться
                            </Link>
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
