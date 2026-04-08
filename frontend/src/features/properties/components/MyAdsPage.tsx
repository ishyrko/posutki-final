'use client';

import { useMemo } from 'react';
import type { ExchangeRates } from '@/features/properties/api';
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from '@/features/properties/price-display';
import { motion } from 'framer-motion';
import Link from 'next/link';
import { Plus, Edit, Eye, EyeOff, Trash2, MapPin, BedDouble, Maximize, Clock, BarChart3, Rocket } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useBoostProperty, useExchangeRates, useMyProperties } from '@/features/properties/hooks';
import { Property, formatAddress } from '@/features/properties/types';
import { buildPropertyUrl } from '@/features/catalog/slugs';

export type MyAdsStatus = 'published' | 'moderation' | 'rejected' | 'inactive';

const STATUS_CONFIG: Record<Property['status'], { label: string; className: string }> = {
    draft: { label: 'Черновик', className: 'bg-yellow-100 text-yellow-800' },
    moderation: { label: 'Ожидает модерации', className: 'bg-blue-100 text-blue-800' },
    rejected: { label: 'Отклонено', className: 'bg-red-100 text-red-800' },
    published: { label: 'Опубликовано', className: 'bg-green-100 text-green-800' },
    archived: { label: 'В архиве', className: 'bg-gray-100 text-gray-600' },
    deleted: { label: 'Удалено', className: 'bg-gray-100 text-gray-500' },
};

const STATUS_TABS: Array<{ status: MyAdsStatus; label: string }> = [
    { status: 'published', label: 'Опубликовано' },
    { status: 'moderation', label: 'На модерации' },
    { status: 'rejected', label: 'Отклоненные' },
    { status: 'inactive', label: 'Неактивные' },
];

const STATUS_ROUTE_SEGMENTS: Record<MyAdsStatus, string> = {
    published: 'aktivnye',
    moderation: 'moderatsiya',
    rejected: 'otklonennye',
    inactive: 'neaktivnye',
};

const MS_PER_HOUR = 60 * 60 * 1000;

/** Boost: earliest 24h after listing creation */
function isAtLeast24HoursAfterCreation(createdAtIso: string | undefined): boolean {
    if (!createdAtIso) return false;
    const created = new Date(createdAtIso).getTime();
    if (Number.isNaN(created)) return false;
    return Date.now() - created >= 24 * MS_PER_HOUR;
}

function isBoostedToday(iso: string | null | undefined): boolean {
    if (!iso) return false;
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return false;
    const now = new Date();
    return (
        d.getFullYear() === now.getFullYear() &&
        d.getMonth() === now.getMonth() &&
        d.getDate() === now.getDate()
    );
}

function getBoostErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }
    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }
    const nestedError = (data as { error?: { message?: unknown } }).error?.message;
    if (typeof nestedError === 'string' && nestedError.length > 0) {
        return nestedError;
    }
    return fallback;
}

function ListingCard({ property }: { property: Property }) {
    const boost = useBoostProperty();
    const { data: rates } = useExchangeRates();
    const exchangeRates: ExchangeRates = useMemo(
        () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
        [rates],
    );
    const { primary: pricePrimary, secondary: priceSecondary } = formatPropertyPrices(property, exchangeRates);
    const isActive = property.status === 'published';
    const address = formatAddress(property.address);
    const statusConfig = STATUS_CONFIG[property.status];
    const propertyHref = buildPropertyUrl(property.type, property.id);
    const boostedToday = isBoostedToday(property.boostedAt);
    const boostCooldownOk = isAtLeast24HoursAfterCreation(property.createdAt);

    return (
        <div className="w-full max-w-full flex flex-col sm:flex-row bg-card rounded-2xl border border-border overflow-hidden shadow-card">
            <Link href={propertyHref} className="relative sm:w-48 flex-shrink-0 aspect-[4/3] sm:aspect-auto sm:h-auto overflow-hidden">
                {property.images[0] ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                        src={property.images[0].thumbnailUrl || property.images[0].url}
                        alt={property.title}
                        className="absolute inset-0 w-full h-full object-cover"
                    />
                ) : (
                    <div className="absolute inset-0 w-full h-full bg-muted flex items-center justify-center text-muted-foreground text-sm">
                        Нет фото
                    </div>
                )}
                {!isActive && (
                    <div className="absolute inset-0 bg-foreground/40 flex items-center justify-center">
                        <span className={`text-xs font-medium px-2.5 py-1 rounded-full ${statusConfig.className}`}>
                            {statusConfig.label}
                        </span>
                    </div>
                )}
            </Link>
            <div className="flex-1 min-w-0 p-4 sm:p-5 flex flex-col justify-between">
                <div>
                    <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                        <div className="min-w-0">
                            <Link
                                href={propertyHref}
                                className="block font-semibold text-foreground hover:text-primary transition-colors break-words line-clamp-2"
                            >
                                {property.title}
                            </Link>
                            <span className={`inline-flex mt-2 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap ${statusConfig.className}`}>
                                {statusConfig.label}
                            </span>
                            {property.pendingRevisionStatus === 'pending' && (
                                <span className="inline-flex mt-2 ml-2 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap bg-blue-100 text-blue-800">
                                    Изменения на проверке
                                </span>
                            )}
                        </div>
                        <div className="text-lg font-bold text-foreground whitespace-nowrap sm:text-right space-y-0.5">
                            <div>{pricePrimary}</div>
                            <div className="text-xs font-normal text-muted-foreground">{priceSecondary}</div>
                        </div>
                    </div>
                    <p className="text-sm text-muted-foreground flex items-center gap-1 mt-2 min-w-0">
                        <MapPin className="w-3.5 h-3.5 shrink-0" />
                        <span className="truncate">{address}</span>
                    </p>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1">
                            <BedDouble className="w-3.5 h-3.5" />
                            {property.specifications.rooms} комн.
                        </span>
                        <span className="flex items-center gap-1">
                            <Maximize className="w-3.5 h-3.5" />
                            {property.type === 'land'
                                ? (property.specifications.landArea ? `${property.specifications.landArea} сот.` : '-')
                                : `${property.specifications.area} м²`}
                        </span>
                    </div>
                    {property.status === 'rejected' && property.moderationComment && (
                        <p className="mt-2 text-xs text-red-700 bg-red-50 border border-red-200 rounded-md px-2 py-1.5">
                            Причина отклонения: {property.moderationComment}
                        </p>
                    )}
                    {property.pendingRevisionStatus === 'rejected' && property.pendingRevisionComment && (
                        <p className="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-2 py-1.5">
                            Причина отклонения изменений: {property.pendingRevisionComment}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-3 pt-3 border-t border-border">
                    <Button variant="ghost" size="sm" asChild className="justify-start">
                        <Link href={`/kabinet/redaktirovat/${property.id}/`}>
                            <Edit className="w-3.5 h-3.5 mr-1" />Изменить
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild className="justify-start">
                        <Link href={`/kabinet/statistika/${property.id}/`}>
                            <BarChart3 className="w-3.5 h-3.5 mr-1" />Статистика
                        </Link>
                    </Button>
                    {property.status === 'published' &&
                        (!boostCooldownOk ? (
                            <Button variant="ghost" size="sm" disabled className="justify-start text-muted-foreground">
                                <Rocket className="w-3.5 h-3.5 mr-1" />
                                Через 24 ч после создания
                            </Button>
                        ) : boostedToday ? (
                            <Button variant="ghost" size="sm" disabled className="justify-start text-muted-foreground">
                                <Rocket className="w-3.5 h-3.5 mr-1" />
                                Поднято сегодня
                            </Button>
                        ) : (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="justify-start"
                                disabled={boost.isPending && boost.variables === property.id}
                                onClick={() =>
                                    boost.mutate(property.id, {
                                        onSuccess: () => toast.success('Объявление поднято в топ'),
                                        onError: (e) =>
                                            toast.error(getBoostErrorMessage(e, 'Не удалось поднять объявление')),
                                    })
                                }
                            >
                                <Rocket className="w-3.5 h-3.5 mr-1" />
                                Поднять в топ
                            </Button>
                        ))}
                    {property.status === 'moderation' ? (
                        <Button variant="ghost" size="sm" disabled className="justify-start text-muted-foreground">
                            <Clock className="w-3.5 h-3.5 mr-1" />На модерации
                        </Button>
                    ) : property.status === 'rejected' ? (
                        <Button variant="ghost" size="sm" disabled className="justify-start text-muted-foreground">
                            <Clock className="w-3.5 h-3.5 mr-1" />Отклонено модерацией
                        </Button>
                    ) : (
                        <Button variant="ghost" size="sm" className="justify-start">
                            {isActive
                                ? <><EyeOff className="w-3.5 h-3.5 mr-1" />Скрыть</>
                                : <><Eye className="w-3.5 h-3.5 mr-1" />Показать</>
                            }
                        </Button>
                    )}
                    <Button variant="ghost" size="sm" className="justify-start text-destructive hover:text-destructive">
                        <Trash2 className="w-3.5 h-3.5 mr-1" />Удалить
                    </Button>
                </div>
            </div>
        </div>
    );
}

function isStatusMatch(propertyStatus: Property['status'], tabStatus: MyAdsStatus) {
    if (tabStatus === 'inactive') {
        return propertyStatus === 'archived' || propertyStatus === 'deleted';
    }
    return propertyStatus === tabStatus;
}

export function MyAdsPage({ activeStatus }: { activeStatus: MyAdsStatus }) {
    const { data, isLoading } = useMyProperties();
    const properties = useMemo(() => data?.data ?? [], [data?.data]);

    const filteredProperties = useMemo(() => {
        return properties.filter((property) => isStatusMatch(property.status, activeStatus));
    }, [activeStatus, properties]);

    const statusCounts = useMemo(() => {
        return {
            published: properties.filter((property) => property.status === 'published').length,
            moderation: properties.filter((property) => property.status === 'moderation').length,
            rejected: properties.filter((property) => property.status === 'rejected').length,
            inactive: properties.filter((property) => property.status === 'archived' || property.status === 'deleted').length,
        };
    }, [properties]);

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
        >
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h1 className="text-2xl font-display font-bold text-foreground">Мои объявления</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        {filteredProperties.length} объявлений
                    </p>
                </div>
                <Button asChild className="bg-gradient-primary text-primary-foreground border-0 w-full sm:w-auto">
                    <Link href="/razmestit/">
                        <Plus className="w-4 h-4 mr-2" />
                        Новое объявление
                    </Link>
                </Button>
            </div>

            <div className="flex flex-wrap gap-2 mb-6">
                {STATUS_TABS.map((tab) => (
                    <Link
                        key={tab.status}
                        href={`/kabinet/moi-obyavleniya/${STATUS_ROUTE_SEGMENTS[tab.status]}/`}
                        className={cn(
                            'inline-flex items-center rounded-full border px-3 py-1.5 text-sm transition-colors',
                            activeStatus === tab.status
                                ? 'border-primary/30 bg-primary/10 text-primary'
                                : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground'
                        )}
                    >
                        {tab.label} ({statusCounts[tab.status]})
                    </Link>
                ))}
            </div>

            {isLoading ? (
                <div className="space-y-4">
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="h-36 bg-card rounded-xl border border-border animate-pulse" />
                    ))}
                </div>
            ) : filteredProperties.length === 0 ? (
                <div className="text-center py-16 bg-card rounded-xl border border-border">
                    {properties.length === 0 ? (
                        <>
                            <p className="text-muted-foreground mb-4">У вас пока нет объявлений</p>
                            <Button asChild className="bg-gradient-primary text-primary-foreground border-0">
                                <Link href="/razmestit/">
                                    <Plus className="w-4 h-4 mr-2" />
                                    Создать первое объявление
                                </Link>
                            </Button>
                        </>
                    ) : (
                        <p className="text-muted-foreground">
                            В этом статусе объявлений нет
                        </p>
                    )}
                </div>
            ) : (
                <div className="space-y-4">
                    {filteredProperties.map((property) => (
                        <ListingCard key={property.id} property={property} />
                    ))}
                </div>
            )}
        </motion.div>
    );
}
