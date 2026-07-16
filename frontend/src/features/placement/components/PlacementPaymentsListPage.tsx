'use client';

import Link from 'next/link';
import { motion } from 'framer-motion';
import { CreditCard, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { BynCurrencyMark } from '@/components/BynCurrency';
import { useMyPlacementPurchases } from '@/features/placement/hooks';
import { isPlacementPurchasePayable, type PlacementPurchase } from '@/features/placement/types';
import { cn } from '@/lib/utils';

function formatDuration(months: number): string {
    if (months === 1) {
        return '1 месяц';
    }
    if (months < 5) {
        return `${months} месяца`;
    }
    return `${months} месяцев`;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function PurchaseRow({ purchase }: { purchase: PlacementPurchase }) {
    const isPayable = isPlacementPurchasePayable(purchase);

    return (
        <div
            className={cn(
                'rounded-xl border p-4 bg-card shadow-card transition-colors',
                isPayable ? 'border-primary/40 bg-primary/[0.03]' : 'border-border',
            )}
        >
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="font-medium text-foreground truncate">
                            {purchase.propertyTitle ?? `Объявление #${purchase.propertyId}`}
                        </h2>
                        {isPayable && (
                            <span className="inline-flex items-center rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold text-primary-foreground">
                                К оплате
                            </span>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {purchase.kindLabel}
                        {purchase.level != null ? ` · VIP ${purchase.level}` : ''}
                        {purchase.durationMonths != null
                            ? ` · ${formatDuration(purchase.durationMonths)}`
                            : ''}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Создана {formatDate(purchase.createdAt)}
                        {purchase.reservationExpiresAt && purchase.status === 'pending_payment' && (
                            <>
                                {' · '}
                                бронь до{' '}
                                {new Date(purchase.reservationExpiresAt).toLocaleString('ru-RU', {
                                    day: 'numeric',
                                    month: 'short',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })}
                            </>
                        )}
                    </p>
                </div>

                <div className="flex shrink-0 flex-col items-start gap-3 sm:items-end">
                    <div className="text-left sm:text-right">
                        <div className="text-lg font-bold text-foreground inline-flex items-baseline gap-1">
                            {purchase.priceByn} <BynCurrencyMark />
                        </div>
                        <div
                            className={cn(
                                'text-xs mt-0.5',
                                isPayable ? 'font-medium text-primary' : 'text-muted-foreground',
                            )}
                        >
                            {purchase.statusLabel}
                        </div>
                    </div>
                    {isPayable ? (
                        <Button asChild size="sm" className="w-full sm:w-auto">
                            <Link href={`/kabinet/oplata/${purchase.id}/`}>Оплатить</Link>
                        </Button>
                    ) : (
                        <Button asChild variant="outline" size="sm" className="w-full sm:w-auto">
                            <Link href={`/kabinet/oplata/${purchase.id}/`}>Подробнее</Link>
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}

export function PlacementPaymentsListPage() {
    const { data: purchases = [], isLoading } = useMyPlacementPurchases();
    const sortedPurchases = [...purchases].sort((a, b) => {
        const aPayable = isPlacementPurchasePayable(a);
        const bPayable = isPlacementPurchasePayable(b);
        if (aPayable !== bPayable) {
            return aPayable ? -1 : 1;
        }
        return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
    });
    const payableCount = purchases.filter(isPlacementPurchasePayable).length;

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
        >
            <h1 className="font-display text-2xl font-bold text-foreground mb-1">Оплаты</h1>
            <p className="text-sm text-muted-foreground mb-6">
                {isLoading
                    ? 'Загрузка...'
                    : payableCount > 0
                      ? `${payableCount} ожида${payableCount === 1 ? 'ет' : 'ют'} оплаты · всего ${purchases.length}`
                      : purchases.length > 0
                        ? `${purchases.length} заяв${purchases.length === 1 ? 'ка' : purchases.length < 5 ? 'ки' : 'ок'}`
                        : 'История оплат за размещение'}
            </p>

            {isLoading ? (
                <div className="flex justify-center py-16">
                    <Loader2 className="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
            ) : purchases.length === 0 ? (
                <div className="text-center py-16 bg-card rounded-xl shadow-card">
                    <CreditCard className="w-10 h-10 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground">У вас пока нет оплат</p>
                    <p className="text-sm text-muted-foreground/60 mt-1">
                        Заявки на платное размещение появятся здесь после оформления
                    </p>
                    <Button asChild variant="outline" className="mt-4">
                        <Link href="/kabinet/moi-obyavleniya/aktivnye/">К объявлениям</Link>
                    </Button>
                </div>
            ) : (
                <div className="space-y-3">
                    {sortedPurchases.map((purchase) => (
                        <PurchaseRow key={purchase.id} purchase={purchase} />
                    ))}
                </div>
            )}
        </motion.div>
    );
}
