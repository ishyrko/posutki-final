'use client';

import Link from 'next/link';
import { toast } from 'sonner';
import { ArrowLeft, CreditCard, Landmark, Wallet } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { BynCurrencyMark } from '@/components/BynCurrency';
import {
    usePlacementPurchase,
    usePropertyPlacementPurchases,
} from '@/features/placement/hooks';
import { cn } from '@/lib/utils';

const PAYMENT_METHODS = [
    { id: 'card', label: 'Банковская карта', icon: CreditCard },
    { id: 'erip', label: 'ЕРИП', icon: Landmark },
    { id: 'balance', label: 'Баланс', icon: Wallet },
] as const;

export function PlacementPaymentPage({ purchaseId }: { purchaseId: number }) {
    const { data: purchase, isLoading, isError } = usePlacementPurchase(purchaseId);
    const { data: history = [] } = usePropertyPlacementPurchases(purchase?.propertyId);

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
        toast.success('Заявка отправлена, ожидает подтверждения администратором');
    };

    return (
        <div className="max-w-2xl mx-auto space-y-6">
            <div>
                <Button variant="ghost" size="sm" asChild className="mb-3 -ml-2">
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/">
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        К объявлениям
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
                            {purchase.typeLabel}
                            {purchase.slotLabel ? ` · позиции ${purchase.slotLabel}` : ''}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Срок</dt>
                        <dd className="font-medium text-foreground">
                            {purchase.durationMonths}{' '}
                            {purchase.durationMonths === 1
                                ? 'месяц'
                                : purchase.durationMonths < 5
                                  ? 'месяца'
                                  : 'месяцев'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Сумма</dt>
                        <dd className="font-bold text-foreground inline-flex items-baseline gap-1">
                            {purchase.priceByn} <BynCurrencyMark />
                        </dd>
                    </div>
                </dl>
            </div>

            <div className="bg-card rounded-xl border border-border p-5 shadow-card">
                <h2 className="font-semibold text-foreground mb-4">Способ оплаты</h2>
                <div className="space-y-2 mb-5">
                    {PAYMENT_METHODS.map((method) => (
                        <div
                            key={method.id}
                            className="flex items-center gap-3 p-3 rounded-lg border border-border opacity-60"
                        >
                            <method.icon className="w-5 h-5 text-muted-foreground" />
                            <div className="flex-1">
                                <p className="text-sm font-medium text-foreground">{method.label}</p>
                                <p className="text-xs text-muted-foreground">Скоро</p>
                            </div>
                            <span className="text-[10px] uppercase tracking-wide font-semibold text-muted-foreground bg-muted px-2 py-0.5 rounded">
                                В разработке
                            </span>
                        </div>
                    ))}
                </div>
                <Button
                    className="w-full sm:w-auto"
                    disabled={purchase.status !== 'pending_payment'}
                    onClick={handlePayClick}
                >
                    Оплатить
                </Button>
                {purchase.status === 'pending_payment' && (
                    <p className="text-xs text-muted-foreground mt-3">
                        Онлайн-оплата пока не подключена. Нажмите «Оплатить», чтобы подтвердить заявку —
                        администратор активирует размещение после поступления средств.
                    </p>
                )}
                {purchase.status === 'active' && (
                    <p className="text-xs text-green-700 mt-3">Заявка активирована.</p>
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
                                            {item.typeLabel}
                                            {item.slotLabel ? ` (${item.slotLabel})` : ''},{' '}
                                            {item.durationMonths} мес.
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
