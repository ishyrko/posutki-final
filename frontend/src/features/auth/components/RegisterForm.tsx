'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useRegister, useGoogleLogin } from '../hooks';
import { registerSchema, RegisterCredentials } from '../api';
import Link from 'next/link';
import { Eye, EyeOff, Mail, Phone, ArrowLeft } from 'lucide-react';
import { useMemo, useState } from 'react';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { PhoneAuthPanel } from '@/features/sms-auth/components/PhoneAuthPanel';
import { useRouter } from 'next/navigation';
import { GoogleMark } from './GoogleMark';

type RegisterMethod = 'email' | 'phone';

const registerUiSchema = z.object({
    fullName: z.string().min(1, 'Укажите имя и фамилию'),
    email: registerSchema.shape.email,
    password: registerSchema.shape.password,
});

type RegisterUiFormValues = z.infer<typeof registerUiSchema>;

export function RegisterForm() {
    const router = useRouter();
    const { mutate: registerUser, isPending } = useRegister();
    const { trigger: triggerGoogleLogin } = useGoogleLogin();
    const [showPassword, setShowPassword] = useState(false);
    const [agreed, setAgreed] = useState(false);
    const [method, setMethod] = useState<RegisterMethod>('email');

    const form = useForm<RegisterUiFormValues>({
        resolver: zodResolver(registerUiSchema),
        defaultValues: {
            fullName: '',
            email: '',
            password: '',
        },
    });

    const passwordValue = form.watch('password');

    const passwordStrength = useMemo(() => {
        if (passwordValue.length === 0) return null;
        if (passwordValue.length < 6) return { label: 'Слабый', color: 'bg-destructive', width: 'w-1/4' as const };
        if (passwordValue.length < 10) return { label: 'Средний', color: 'bg-yellow-500', width: 'w-2/4' as const };
        return { label: 'Надёжный', color: 'bg-green-500', width: 'w-full' as const };
    }, [passwordValue]);

    const onSubmit = (data: RegisterUiFormValues) => {
        const parts = data.fullName.trim().split(/\s+/).filter(Boolean);
        if (parts.length < 2) {
            form.setError('fullName', { type: 'manual', message: 'Укажите имя и фамилию' });
            return;
        }
        const firstName = parts[0] ?? '';
        const lastName = parts.slice(1).join(' ');
        const payload: RegisterCredentials = {
            firstName,
            lastName,
            email: data.email,
            password: data.password,
        };
        const parsed = registerSchema.safeParse(payload);
        if (!parsed.success) {
            const issue = parsed.error.issues.find(
                (i) => i.path[0] === 'firstName' || i.path[0] === 'lastName',
            );
            form.setError('fullName', {
                type: 'manual',
                message: issue?.message ?? 'Проверьте имя и фамилию',
            });
            return;
        }
        registerUser(parsed.data);
    };

    const afterPhoneAuth = () => {
        router.push('/kabinet/');
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
                            <h1 className="text-xl font-semibold text-foreground mt-4">Создать аккаунт</h1>
                            <p className="text-muted-foreground text-sm mt-1">Присоединяйтесь к сервису посуточной аренды</p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            className="w-full h-12 text-sm font-medium gap-3"
                            onClick={triggerGoogleLogin}
                        >
                            <GoogleMark className="w-5 h-5" />
                            Зарегистрироваться через Google
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
                                    <Label htmlFor="reg-full-name">Имя и фамилия</Label>
                                    <Input
                                        id="reg-full-name"
                                        type="text"
                                        placeholder="Иван Петров"
                                        className="h-11"
                                        {...form.register('fullName')}
                                    />
                                    {form.formState.errors.fullName && (
                                        <p className="text-destructive text-xs">{form.formState.errors.fullName.message}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="reg-email">Email</Label>
                                    <Input
                                        id="reg-email"
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
                                    <Label htmlFor="reg-password">Пароль</Label>
                                    <div className="relative">
                                        <Input
                                            id="reg-password"
                                            type={showPassword ? 'text' : 'password'}
                                            placeholder="Минимум 6 символов"
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
                                    {passwordStrength && (
                                        <div className="space-y-1">
                                            <div className="h-1.5 bg-muted rounded-full overflow-hidden">
                                                <div
                                                    className={`h-full ${passwordStrength.color} ${passwordStrength.width} rounded-full transition-all duration-300`}
                                                />
                                            </div>
                                            <p className="text-xs text-muted-foreground">{passwordStrength.label}</p>
                                        </div>
                                    )}
                                    {form.formState.errors.password && (
                                        <p className="text-destructive text-xs">{form.formState.errors.password.message}</p>
                                    )}
                                </div>
                                <div className="flex items-start gap-2">
                                    <Checkbox
                                        id="agree-email"
                                        checked={agreed}
                                        onCheckedChange={(v) => setAgreed(v === true)}
                                        className="mt-0.5"
                                    />
                                    <label htmlFor="agree-email" className="text-xs text-muted-foreground leading-relaxed cursor-pointer">
                                        Я согласен с{' '}
                                        <Link href="/politika-konfidentsialnosti/" className="text-primary hover:underline">
                                            Политикой конфиденциальности
                                        </Link>{' '}
                                        и{' '}
                                        <Link href="/usloviya-ispolzovaniya/" className="text-primary hover:underline">
                                            условиями использования
                                        </Link>
                                    </label>
                                </div>
                                <Button type="submit" disabled={!agreed || isPending} className="w-full h-11">
                                    {isPending ? 'Загрузка...' : 'Создать аккаунт'}
                                </Button>
                            </form>
                        ) : (
                            <PhoneAuthPanel
                                key="register-phone"
                                onAuthenticated={afterPhoneAuth}
                                consentAccepted={agreed}
                                consentSlot={
                                    <div className="flex items-start gap-2">
                                        <Checkbox
                                            id="agree-phone"
                                            checked={agreed}
                                            onCheckedChange={(v) => setAgreed(v === true)}
                                            className="mt-0.5"
                                        />
                                        <label
                                            htmlFor="agree-phone"
                                            className="text-xs text-muted-foreground leading-relaxed cursor-pointer"
                                        >
                                            Я согласен с{' '}
                                            <Link href="/politika-konfidentsialnosti/" className="text-primary hover:underline">
                                                Политикой конфиденциальности
                                            </Link>{' '}
                                            и{' '}
                                            <Link href="/usloviya-ispolzovaniya/" className="text-primary hover:underline">
                                                условиями использования
                                            </Link>
                                        </label>
                                    </div>
                                }
                            />
                        )}

                        <p className="text-center text-sm text-muted-foreground mt-6">
                            Уже есть аккаунт?{' '}
                            <Link href="/login/" className="text-primary font-medium hover:underline">
                                Войти
                            </Link>
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
