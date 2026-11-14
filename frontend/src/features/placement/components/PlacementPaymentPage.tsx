'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { toast } from 'sonner';
import { ArrowLeft, CreditCard, QrCode } from 'lucide-react';
import type { PlacementPaymentMethod } from '@/features/placement/api';
import { Button } from '@/components/ui/button';
import { BynCurrencyMark } from '@/components/BynCurrency';
import {
    useConfirmPlacementPayment,
    useCreatePlacementPayment,
    usePlacementPurchase,
    usePropertyPlacementPurchases,
} from '@/features/placement/hooks';
import {
    formatPlacementPurchasePeriod,
    formatPlacementPurchaseSummary,
} from '@/features/placement/types';
import { cn } from '@/lib/utils';

function getApiErrorMessage(error: unknown, fallback: string): string {
    if (error && typeof error === 'object' && 'response' in error) {
        const response = (error as { response?: { data?: { message?: string; error?: string | { message?: string } } } })
            .response;
        const err = response?.data?.error;
        const message =
            response?.data?.message ??
            (typeof err === 'string' ? err : err?.message);
        if (typeof message === 'string' && message.trim() !== '') {
            return message;
        }
    }
    if (error instanceof Error && error.message) {
        return error.message;
    }
    return fallback;
}

const PAYMENT_METHOD_OPTIONS: Array<{
    id: PlacementPaymentMethod;
    title: string;
    description: string;
    icon: typeof CreditCard;
}> = [
    {
        id: 'epos',
        title: 'ЕРИП (E-POS)',
        description:
            'Оплата по QR-коду или через ЕРИП в интернет-банке. Зачисление может занять несколько минут',
        icon: QrCode,
    },
    {
        id: 'card',
        title: 'Банковская карта',
        description: 'Visa, Mastercard, Белкарт — безопасная страница bePaid',
        icon: CreditCard,
    },
];

export function PlacementPaymentPage({ purchaseId }: { purchaseId: number }) {
    const router = useRouter();
    const searchParams = useSearchParams();
    const returnStatus = searchParams.get('status');
    const checkoutToken = searchParams.get('token');
    const isSuccessReturn =
        returnStatus === 'success' || returnStatus === 'successful';
    const hasReturnParams = returnStatus !== null;
    const [awaitingActivation, setAwaitingActivation] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState<PlacementPaymentMethod>('epos');
    const { data: purchase, isLoading, isError, refetch } = usePlacementPurchase(purchaseId, {
        pollWhilePending: awaitingActivation,
    });
    const { data: history = [] } = usePropertyPlacementPurchases(purchase?.propertyId);
    const createPayment = useCreatePlacementPayment();
    const { mutate: confirmPlacementPayment } = useConfirmPlacementPayment();
    const confirmedRef = useRef(false);
    const awaitingTokenRef = useRef(false);

    const cleanReturnUrl = useCallback(() => {
        router.replace(`/kabinet/oplata/${purchaseId}/`);
    }, [purchaseId, router]);

    useEffect(() => {
        if (!hasReturnParams || isLoading || !purchase) {
            return;
        }

        if (purchase.status === 'active' && isSuccessReturn) {
            cleanReturnUrl();
        }
    }, [cleanReturnUrl, hasReturnParams, isLoading, isSuccessReturn, purchase]);

    useEffect(() => {
        if (!returnStatus || confirmedRef.current) {
            return;
        }

        if (isSuccessReturn) {
            if (!checkoutToken) {
                if (awaitingTokenRef.current) {
                    return;
                }
                awaitingTokenRef.current = true;
                queueMicrotask(() => setAwaitingActivation(true));
                toast.message('Оплата получена, подтверждаем статус…');
                void refetch();
                cleanReturnUrl();
                return;
            }

            confirmedRef.current = true;
            queueMicrotask(() => setAwaitingActivation(true));
            cleanReturnUrl();
            confirmPlacementPayment(
                { purchaseId, token: checkoutToken },
                {
                    onSuccess: (confirmedPurchase) => {
                        if (confirmedPurchase.status === 'active') {
                            toast.success('Оплата прошла успешно, размещение активировано');
                        } else {
                            toast.message('Оплата получена, активируем размещение…');
                        }
                    },
                    onError: () => {
                        toast.message('Оплата получена, подтверждаем статус…');
                        void refetch();
                    },
                },
            );
            return;
        }

        confirmedRef.current = true;
        if (returnStatus === 'decline' || returnStatus === 'fail' || returnStatus === 'failed') {
            toast.error('Платёж отклонён. Попробуйте снова или выберите другой способ оплаты.');
        } else if (returnStatus === 'cancel') {
            toast.message('Оплата отменена');
        }
        cleanReturnUrl();
        void refetch();
    }, [
        checkoutToken,
        cleanReturnUrl,
        confirmPlacementPayment,
        isSuccessReturn,
        purchaseId,
        refetch,
        returnStatus,
    ]);

    if (isLoading) {
        return (
            <div className="py-12 text-center text-muted-foreground">Загрузка заявки…</div>
        );
    }

    if (isError || !purchase) {
        return (
            <div className="py-12 text-center space-y-4">
                <p className="text-muted-foreground">Заявка не найдена</p>
                <Button asChild variant="outline">
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/">К объявлениям</Link>
                </Button>
            </div>
        );
    }

    const handlePayClick = () => {
        createPayment.mutate(
            { purchaseId: purchase.id, method: paymentMethod },
            {
                onSuccess: (data) => {
                    window.location.href = data.redirectUrl;
                },
                onError: (e) =>
                    toast.error(getApiErrorMessage(e, 'Не удалось перейти к оплате')),
            },
        );
    };

    const canPay = purchase.status === 'pending_payment' && !createPayment.isPending;

    return (
        <div className="max-w-2xl mx-auto space-y-6">
            <div>
                <Button variant="ghost" size="sm" asChild className="mb-3 -ml-2">
                    <Link href="/kabinet/oplata/">
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        К оплатам
                    </Link>
                </Button>
                <h1 className="text-2xl font-display font-bold text-foreground">Оплата размещения</h1>
                <p className="text-sm text-muted-foreground mt-1">
                    Статус: <span className="font-medium text-foreground">{purchase.statusLabel}</span>
                </p>
            </div>

            <div className="bg-card rounded-xl border border-border p-5 shadow-card space-y-3">
                <h2 className="font-semibold text-foreground">Сводка заказа</h2>
                <dl className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Объявление</dt>
                        <dd className="font-medium text-foreground">
                            {purchase.propertyTitle ?? `#${purchase.propertyId}`}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Тип</dt>
                        <dd className="font-medium text-foreground">
                            {purchase.kindLabel}
                            {purchase.level != null ? ` · VIP ${purchase.level}` : ''}
                        </dd>
                    </div>
                    {formatPlacementPurchasePeriod(purchase) != null && (
                        <div>
                            <dt className="text-muted-foreground">Срок</dt>
                            <dd className="font-medium text-foreground">
                                {formatPlacementPurchasePeriod(purchase)}
                            </dd>
                        </div>
                    )}
                    <div>
                        <dt className="text-muted-foreground">Сумма</dt>
                        <dd className="font-bold text-foreground inline-flex items-baseline gap-1">
                            {purchase.priceByn} <BynCurrencyMark />
                        </dd>
                    </div>
                </dl>
            </div>

            <div className="bg-card rounded-xl border border-border p-5 shadow-card">
                <h2 className="font-semibold text-foreground mb-4">Оплата</h2>
                <div className="grid gap-3 mb-5" role="group" aria-label="Способ оплаты">
                    {PAYMENT_METHOD_OPTIONS.map((option) => {
                        const Icon = option.icon;
                        const selected = paymentMethod === option.id;

                        return (
                            <button
                                key={option.id}
                                type="button"
                                aria-pressed={selected}
                                disabled={!canPay}
                                onClick={() => setPaymentMethod(option.id)}
                                className={cn(
                                    'flex items-center gap-3 p-3 rounded-lg border text-left transition-colors',
                                    selected
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/40',
                                    'disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:border-border',
                                )}
                            >
                                <Icon className="w-5 h-5 shrink-0 text-muted-foreground" />
                                <span className="flex-1">
                                    <span className="block text-sm font-medium text-foreground">
                                        {option.title}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {option.description}
                                    </span>
                                </span>
                            </button>
                        );
                    })}
                </div>
                <Button
                    className="w-full sm:w-auto"
                    disabled={!canPay}
                    onClick={handlePayClick}
                >
                    {createPayment.isPending ? 'Переход к оплате…' : 'Оплатить онлайн'}
                </Button>
                {purchase.status === 'pending_payment' && paymentMethod === 'card' && (
                    <p className="text-xs text-muted-foreground mt-3">
                        После нажатия вы перейдёте на защищённую страницу оплаты. Размещение
                        активируется автоматически после поступления средств.
                    </p>
                )}
                {purchase.status === 'pending_payment' && paymentMethod === 'epos' && (
                    <p className="text-xs text-muted-foreground mt-3">
                        После оплаты через ЕРИП размещение активируется автоматически, как только
                        поступит платёж. Страницу можно закрыть.
                    </p>
                )}
                {purchase.status === 'active' && (
                    <p className="text-xs text-green-700 mt-3">
                        Заявка активирована. Размещение уже действует для объявления.
                    </p>
                )}
                {purchase.status === 'pending_payment' && awaitingActivation && (
                    <p className="text-xs text-muted-foreground mt-3">
                        Оплата получена, подтверждаем активацию размещения…
                    </p>
                )}
            </div>

            <div className="bg-card rounded-xl border border-border p-5 shadow-card">
                <h2 className="font-semibold text-foreground mb-4">История заявок по объявлению</h2>
                {history.length === 0 ? (
                    <p className="text-sm text-muted-foreground">Пока нет заявок</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-xs text-muted-foreground border-b border-border">
                                    <th className="text-left py-2">Дата</th>
                                    <th className="text-left py-2">Описание</th>
                                    <th className="text-right py-2">Сумма</th>
                                    <th className="text-right py-2">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.map((item) => (
                                    <tr
                                        key={item.id}
                                        className={cn(
                                            'border-b border-border last:border-0',
                                            item.id === purchase.id && 'bg-primary/5',
                                        )}
                                    >
                                        <td className="py-3 text-muted-foreground whitespace-nowrap">
                                            {new Date(item.createdAt).toLocaleDateString('ru-RU')}
                                        </td>
                                        <td className="py-3 text-foreground">
                                            {formatPlacementPurchaseSummary(item)}
                                        </td>
                                        <td className="py-3 text-right font-medium">
                                            <span className="inline-flex items-baseline gap-1 justify-end">
                                                {item.priceByn} <BynCurrencyMark />
                                            </span>
                                        </td>
                                        <td className="py-3 text-right text-xs">{item.statusLabel}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
