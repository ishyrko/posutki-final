'use client';

import { useEffect, useMemo, useState } from 'react';
import type { ExchangeRates } from '@/features/properties/api';
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from '@/features/properties/price-display';
import { motion } from 'framer-motion';
import Link from 'next/link';
import { ListingSubmitLink } from '@/components/ListingSubmitLink';
import { Plus, Edit, Eye, EyeOff, Trash2, MapPin, BedDouble, Maximize, Clock, BarChart3, CalendarDays, Rocket, Star, User, Phone, MessagesSquare, Search, Archive } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { cn } from '@/lib/utils';
import { useArchiveProperty, useDeleteProperty, useExchangeRates, useMyProperties, usePublishFreeProperty, useUnarchiveProperty } from '@/features/properties/hooks';
import { Property, formatAddress, isPropertyEditable } from '@/features/properties/types';
import { buildPropertyUrlFromRegionName } from '@/features/catalog/slugs';
import { PriceInByn } from '@/components/BynCurrency';
import { BuyPlacementDialog } from '@/features/placement/components/BuyPlacementDialog';
import { usePropertyPlacementLevels } from '@/features/placement/hooks';
import {
    FREE_PLACEMENT_LIMITS_HREF,
    formatCatalogPositionRange,
    formatFreeVsVip1CatalogHint,
    formatPlacementStatus,
    isPlacementBoostActive,
    placementBadgeLabel,
    type FreeVsVip1CatalogHint,
} from '@/features/placement/types';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export type MyAdsStatus = 'published' | 'moderation' | 'rejected' | 'awaiting_payment' | 'inactive';

const STATUS_CONFIG: Record<Property['status'], { label: string; className: string }> = {
    draft: { label: 'Черновик', className: 'bg-yellow-100 text-yellow-800' },
    moderation: { label: 'Ожидает модерации', className: 'bg-blue-100 text-blue-800' },
    rejected: { label: 'Отклонено', className: 'bg-red-100 text-red-800' },
    published: { label: 'Опубликовано', className: 'bg-green-100 text-green-800' },
    awaiting_payment: { label: 'Требует оплаты', className: 'bg-orange-100 text-orange-800' },
    archived: { label: 'В архиве', className: 'bg-gray-100 text-gray-600' },
    deleted: { label: 'Удалено', className: 'bg-gray-100 text-gray-500' },
};

const STATUS_TABS: Array<{ status: MyAdsStatus; label: string }> = [
    { status: 'published', label: 'Опубликовано' },
    { status: 'moderation', label: 'На модерации' },
    { status: 'awaiting_payment', label: 'Требует оплаты' },
    { status: 'rejected', label: 'Отклоненные' },
    { status: 'inactive', label: 'Неактивные' },
];

const STATUS_ROUTE_SEGMENTS: Record<MyAdsStatus, string> = {
    published: 'aktivnye',
    moderation: 'moderatsiya',
    awaiting_payment: 'trebuet-oplaty',
    rejected: 'otklonennye',
    inactive: 'neaktivnye',
};

const MS_PER_DAY = 24 * 60 * 60 * 1000;

function getApiErrorMessage(error: unknown, fallback: string): string {
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

function getDeleteEligibility(property: Property) {
    const neverPublished = !property.publishedAt;
    const archivedAt = property.archivedAt ? new Date(property.archivedAt) : null;
    const daysInArchive =
        archivedAt && !Number.isNaN(archivedAt.getTime())
            ? Math.floor((Date.now() - archivedAt.getTime()) / MS_PER_DAY)
            : null;
    const daysUntilDelete = daysInArchive !== null ? Math.max(0, 30 - daysInArchive) : 30;
    const canDelete =
        neverPublished || (property.status === 'archived' && daysUntilDelete === 0);
    const showDeleteButton =
        property.status !== 'deleted' && (neverPublished || property.status === 'archived');

    return { neverPublished, daysUntilDelete, canDelete, showDeleteButton };
}

const AWAITING_PAYMENT_LIMIT_ACTION_HINT =
    ' Оплатите VIP или освободите слот: скройте другое объявление или купите для него VIP.';

function ListingCard({
    property,
    showPublicLinks,
    onRequestDelete,
    selected,
    onToggleSelect,
}: {
    property: Property;
    showPublicLinks: boolean;
    onRequestDelete?: (propertyId: number) => void;
    selected?: boolean;
    onToggleSelect?: (propertyId: number) => void;
}) {
    const archive = useArchiveProperty();
    const unarchive = useUnarchiveProperty();
    const publishFree = usePublishFreeProperty();
    const [placementDialog, setPlacementDialog] = useState<'level' | 'boost' | null>(null);
    const { data: rates } = useExchangeRates();
    const exchangeRates: ExchangeRates = useMemo(
        () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
        [rates],
    );
    const { primaryAmount, secondary: priceSecondary } = formatPropertyPrices(property, exchangeRates);
    const isActive = property.status === 'published';
    const address = formatAddress(property.address);
    const statusConfig = STATUS_CONFIG[property.status];
    const propertyHref = buildPropertyUrlFromRegionName(
        property.type,
        property.id,
        property.address?.regionName,
        property.address?.citySlug,
    );
    const boostActive = isPlacementBoostActive(property.placementBoostExpiresAt);
    const vipBadge = placementBadgeLabel(property.placementEffectiveLevel);
    const { daysUntilDelete, canDelete, showDeleteButton } = getDeleteEligibility(property);

    const isHouse = property.type === 'house';
    const { data: placementLevelsData } = usePropertyPlacementLevels(
        property.status === 'published' ? property.id : null,
    );
    const placementLevels = placementLevelsData?.levels ?? [];
    const freeTierBand = placementLevelsData?.freeTier;
    const locationLabel = placementLevelsData?.scope.locationLabel
        ?? (isHouse ? property.address?.regionName ?? 'области' : property.address?.cityName ?? 'города');
    const effectiveVipLevel = property.placementEffectiveLevel ?? property.placementBaseLevel ?? 0;
    const catalogPositionHint = useMemo((): string | FreeVsVip1CatalogHint | null => {
        if (effectiveVipLevel <= 0) {
            if (!freeTierBand) {
                return null;
            }
            if (!isHouse) {
                const vip1Level = placementLevels.find((item) => item.level === 1);
                if (vip1Level) {
                    const compared = formatFreeVsVip1CatalogHint(
                        freeTierBand,
                        vip1Level,
                        locationLabel,
                    );
                    if (compared) {
                        return compared;
                    }
                }
            }
            return formatCatalogPositionRange(freeTierBand, locationLabel);
        }
        const levelRow = placementLevels.find((item) => item.level === effectiveVipLevel);
        return levelRow ? formatCatalogPositionRange(levelRow, locationLabel) : null;
    }, [effectiveVipLevel, placementLevels, freeTierBand, locationLabel, isHouse]);
    const freeVsVip1Hint =
        catalogPositionHint && typeof catalogPositionHint === 'object'
            ? catalogPositionHint
            : null;
    const plainCatalogPositionHint =
        typeof catalogPositionHint === 'string' ? catalogPositionHint : null;

    const imageBlock = (
        <>
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
            {onToggleSelect ? (
                <div className="absolute top-2 left-2 z-10" onClick={(event) => event.preventDefault()}>
                    <Checkbox
                        checked={selected}
                        onCheckedChange={() => onToggleSelect(property.id)}
                        className="bg-card/90 border-border shadow-sm"
                        aria-label="Выбрать объявление"
                    />
                </div>
            ) : null}
        </>
    );

    const imageClassName =
        'relative sm:w-40 flex-shrink-0 aspect-[4/3] sm:aspect-auto sm:min-h-[7.5rem] overflow-hidden';

    return (
        <div className="w-full max-w-full flex flex-col sm:flex-row bg-card rounded-xl shadow-card overflow-hidden">
            {showPublicLinks ? (
                <Link href={propertyHref} className={imageClassName}>
                    {imageBlock}
                </Link>
            ) : (
                <div className={imageClassName}>{imageBlock}</div>
            )}
            <div className="flex-1 min-w-0 p-4 sm:p-5 flex flex-col justify-between">
                <div>
                    <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                        <div className="min-w-0">
                            {showPublicLinks ? (
                                <Link
                                    href={propertyHref}
                                    className="block font-semibold text-foreground hover:text-primary transition-colors break-words line-clamp-2"
                                >
                                    {property.title}
                                </Link>
                            ) : (
                                <span className="block font-semibold text-foreground break-words line-clamp-2">
                                    {property.title}
                                </span>
                            )}
                            <div className="flex flex-wrap items-center gap-2 mt-2">
                                <span className={`inline-flex text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap ${statusConfig.className}`}>
                                    {statusConfig.label}
                                </span>
                                {vipBadge ? (
                                    <span className="inline-flex text-xs font-bold px-2 py-0.5 rounded-full whitespace-nowrap bg-amber-500 text-white">
                                        {vipBadge}
                                    </span>
                                ) : property.status === 'published' ? (
                                    <TooltipProvider delayDuration={300}>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <span className="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap bg-muted text-muted-foreground">
                                                    Бесплатно
                                                    <Link
                                                        href={FREE_PLACEMENT_LIMITS_HREF}
                                                        className="underline underline-offset-2 hover:text-foreground transition-colors"
                                                    >
                                                        с ограничениями
                                                    </Link>
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent side="bottom" className="max-w-xs text-center">
                                                Ограничения бесплатного размещения (лимит количества объявлений,
                                                фото, видео, Instagram, сайт) описаны на странице тарифов
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                ) : null}
                                {property.pendingRevisionStatus === 'pending' && (
                                    <span className="inline-flex text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap bg-blue-100 text-blue-800">
                                        Изменения на проверке
                                    </span>
                                )}
                                <TooltipProvider delayDuration={300}>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <span
                                                className="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap bg-muted text-emerald-600 cursor-default"
                                            >
                                                <User className="w-3.5 h-3.5" />
                                                {property.views ?? 0}
                                            </span>
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom">
                                            Просмотры объявления
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider delayDuration={300}>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <span
                                                className="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full whitespace-nowrap bg-muted text-emerald-600 cursor-default"
                                            >
                                                <Phone className="w-3.5 h-3.5" />
                                                {property.phoneViews ?? 0}
                                            </span>
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom">
                                            Просмотры контактов
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>
                        <div className="text-lg font-bold text-foreground whitespace-nowrap sm:text-right space-y-0.5">
                            <div><PriceInByn amount={primaryAmount} /></div>
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
                    {property.status === 'awaiting_payment' && (
                        <p className="text-sm text-muted-foreground mt-2">
                            {property.canPublishFree
                                ? 'Свободный слот доступен — можете опубликовать объявление бесплатно.'
                                : `${property.freeLimitBlockIntro ?? 'Ваш лимит бесплатных объявлений исчерпан'}.${AWAITING_PAYMENT_LIMIT_ACTION_HINT}`}
                        </p>
                    )}
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
                    {property.status === 'published' && (
                        <div className="mt-2 text-xs text-muted-foreground bg-muted/50 border border-border rounded-md px-2 py-1.5 space-y-1.5">
                            <p>Размещение: {formatPlacementStatus(property)}</p>
                            {freeVsVip1Hint ? (
                                <>
                                    <p>{freeVsVip1Hint.current}</p>
                                    <p className="font-medium text-foreground rounded-md border border-primary/30 bg-primary/5 px-2 py-1.5">
                                        {freeVsVip1Hint.vip1}
                                    </p>
                                </>
                            ) : plainCatalogPositionHint ? (
                                <p>{plainCatalogPositionHint}</p>
                            ) : null}
                        </div>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-3 pt-3 border-t border-border">
                    {isPropertyEditable(property.status) ? (
                        <Button variant="ghost" size="sm" asChild className="justify-start">
                            <Link href={`/kabinet/redaktirovat/${property.id}/`}>
                                <Edit className="w-3.5 h-3.5 mr-1" />Изменить
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled
                            className="justify-start text-muted-foreground"
                            title="Нельзя изменять удалённое или неактивное объявление"
                        >
                            <Edit className="w-3.5 h-3.5 mr-1" />Изменить
                        </Button>
                    )}
                    <Button variant="ghost" size="sm" asChild className="justify-start">
                        <Link href={`/kabinet/statistika/${property.id}/`}>
                            <BarChart3 className="w-3.5 h-3.5 mr-1" />Статистика
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" asChild className="justify-start">
                        <Link href={`/kabinet/otzyvy/${property.id}/`} className="inline-flex items-center">
                            <MessagesSquare className="w-3.5 h-3.5 mr-1" />
                            Отзывы
                            {(property.unviewedReviewsCount ?? 0) > 0 ? (
                                <span className="ml-1.5 inline-flex min-w-[1rem] h-4 px-1 rounded-full bg-primary text-primary-foreground text-[10px] items-center justify-center leading-none">
                                    {(property.unviewedReviewsCount ?? 0) > 99 ? '99+' : property.unviewedReviewsCount}
                                </span>
                            ) : null}
                        </Link>
                    </Button>
                    {property.dealType === 'daily' && (
                        <Button variant="ghost" size="sm" asChild className="justify-start">
                            <Link href={`/kabinet/kalendar/${property.id}/`}>
                                <CalendarDays className="w-3.5 h-3.5 mr-1" />Календарь
                            </Link>
                        </Button>
                    )}
                    {property.status === 'published' && (
                        <>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="justify-start bg-amber-500 text-white hover:bg-amber-600 hover:text-white"
                                onClick={() => setPlacementDialog('level')}
                            >
                                <Star className="w-3.5 h-3.5 mr-1" />
                                Купить VIP
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="justify-start"
                                onClick={() => setPlacementDialog('boost')}
                                title={
                                    boostActive
                                        ? 'Буст активен — новая покупка продлит срок на 24 часа после окончания'
                                        : undefined
                                }
                            >
                                <Rocket className="w-3.5 h-3.5 mr-1" />
                                {boostActive ? 'Продлить VIP-буст' : 'VIP-буст 24ч'}
                            </Button>
                        </>
                    )}
                    {property.status === 'moderation' ? (
                        <Button variant="ghost" size="sm" disabled className="justify-start text-muted-foreground">
                            <Clock className="w-3.5 h-3.5 mr-1" />На модерации
                        </Button>
                    ) : property.status === 'rejected' ? (
                        <Button variant="ghost" size="sm" disabled className="justify-start text-muted-foreground">
                            <Clock className="w-3.5 h-3.5 mr-1" />Отклонено модерацией
                        </Button>
                    ) : property.status === 'published' ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="justify-start"
                            disabled={archive.isPending && archive.variables === property.id}
                            onClick={() =>
                                archive.mutate(property.id, {
                                    onSuccess: () => toast.success('Объявление скрыто'),
                                    onError: (e) =>
                                        toast.error(getApiErrorMessage(e, 'Не удалось скрыть объявление')),
                                })
                            }
                        >
                            <EyeOff className="w-3.5 h-3.5 mr-1" />Скрыть
                        </Button>
                    ) : property.status === 'awaiting_payment' ? (
                        <>
                            {property.canPublishFree ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="justify-start bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground"
                                    disabled={publishFree.isPending && publishFree.variables === property.id}
                                    onClick={() =>
                                        publishFree.mutate(property.id, {
                                            onSuccess: () => toast.success('Объявление опубликовано'),
                                            onError: (e) =>
                                                toast.error(getApiErrorMessage(e, 'Не удалось опубликовать объявление')),
                                        })
                                    }
                                >
                                    <Eye className="w-3.5 h-3.5 mr-1" />
                                    Опубликовать бесплатно
                                </Button>
                            ) : (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="justify-start bg-amber-500 text-white hover:bg-amber-600 hover:text-white"
                                    onClick={() => setPlacementDialog('level')}
                                >
                                    <Star className="w-3.5 h-3.5 mr-1" />
                                    Оплатить и опубликовать
                                </Button>
                            )}
                            {property.canPublishFree ? (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="justify-start"
                                    onClick={() => setPlacementDialog('level')}
                                >
                                    <Star className="w-3.5 h-3.5 mr-1" />
                                    Оплатить VIP
                                </Button>
                            ) : null}
                            <Button
                                variant="ghost"
                                size="sm"
                                className="justify-start"
                                disabled={archive.isPending && archive.variables === property.id}
                                onClick={() =>
                                    archive.mutate(property.id, {
                                        onSuccess: () => toast.success('Объявление скрыто'),
                                        onError: (e) =>
                                            toast.error(getApiErrorMessage(e, 'Не удалось скрыть объявление')),
                                    })
                                }
                            >
                                <EyeOff className="w-3.5 h-3.5 mr-1" />Скрыть
                            </Button>
                        </>
                    ) : property.status === 'archived' ? (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="justify-start"
                            disabled={unarchive.isPending && unarchive.variables === property.id}
                            onClick={() =>
                                unarchive.mutate(property.id, {
                                    onSuccess: (result) =>
                                        toast.success(result.message),
                                    onError: (e) =>
                                        toast.error(getApiErrorMessage(e, 'Не удалось активировать объявление')),
                                })
                            }
                        >
                            <Eye className="w-3.5 h-3.5 mr-1" />Активировать
                        </Button>
                    ) : null}
                    {showDeleteButton && (
                        canDelete ? (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="justify-start text-destructive hover:text-destructive"
                                onClick={() => onRequestDelete?.(property.id)}
                            >
                                <Trash2 className="w-3.5 h-3.5 mr-1" />Удалить
                            </Button>
                        ) : (
                            <Button
                                variant="ghost"
                                size="sm"
                                disabled
                                className="justify-start text-muted-foreground"
                            >
                                <Trash2 className="w-3.5 h-3.5 mr-1" />
                                Удалить через {daysUntilDelete} дн.
                            </Button>
                        )
                    )}
                </div>
            </div>
            <BuyPlacementDialog
                property={property}
                open={placementDialog !== null}
                mode={placementDialog ?? 'level'}
                onOpenChange={(open) => {
                    if (!open) setPlacementDialog(null);
                }}
            />
        </div>
    );
}

export function MyAdsPage({ activeStatus }: { activeStatus: MyAdsStatus }) {
    const PAGE_SIZE = 20;
    const deletePropertyMutation = useDeleteProperty();
    const archivePropertyMutation = useArchiveProperty();
    const [deleteTargetId, setDeleteTargetId] = useState<number | null>(null);
    const [page, setPage] = useState(1);
    const [searchInput, setSearchInput] = useState('');
    const [q, setQ] = useState('');
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    useEffect(() => {
        setPage(1);
        setSelectedIds([]);
    }, [activeStatus, q]);

    const { data, isLoading } = useMyProperties({
        page,
        limit: PAGE_SIZE,
        status: activeStatus,
        q: q || undefined,
    });
    const properties = useMemo(() => data?.data ?? [], [data?.data]);
    const total = data?.meta.total ?? properties.length;
    const pageCount = Math.max(1, Math.ceil(total / PAGE_SIZE));
    const statusCounts = {
        published: data?.meta.counts?.published ?? 0,
        moderation: data?.meta.counts?.moderation ?? 0,
        awaiting_payment: data?.meta.counts?.awaiting_payment ?? 0,
        rejected: data?.meta.counts?.rejected ?? 0,
        inactive: data?.meta.counts?.inactive ?? 0,
    };
    const canBulkArchive = activeStatus === 'published' || activeStatus === 'awaiting_payment';
    const closeDeleteDialog = () => setDeleteTargetId(null);

    const toggleSelected = (propertyId: number) => {
        setSelectedIds((current) =>
            current.includes(propertyId)
                ? current.filter((id) => id !== propertyId)
                : [...current, propertyId],
        );
    };

    const handleBulkArchive = async () => {
        if (selectedIds.length === 0) {
            return;
        }
        try {
            await Promise.all(selectedIds.map((id) => archivePropertyMutation.mutateAsync(id)));
            toast.success(`Скрыто объявлений: ${selectedIds.length}`);
            setSelectedIds([]);
        } catch (error) {
            toast.error(getApiErrorMessage(error, 'Не удалось скрыть объявления'));
        }
    };

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
        >
            <AlertDialog open={deleteTargetId !== null} onOpenChange={(open) => !open && closeDeleteDialog()}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Удалить объявление?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Объявление будет удалено без возможности восстановления.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Отмена</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deletePropertyMutation.isPending}
                            onClick={() => {
                                if (deleteTargetId === null) return;
                                deletePropertyMutation.mutate(deleteTargetId, {
                                    onSuccess: () => {
                                        closeDeleteDialog();
                                        toast.success('Объявление удалено');
                                    },
                                    onError: (e) =>
                                        toast.error(getApiErrorMessage(e, 'Не удалось удалить объявление')),
                                });
                            }}
                        >
                            Удалить
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h1 className="font-display text-2xl font-bold text-foreground">Мои объявления</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        {total} в этом статусе
                    </p>
                </div>
                <Button asChild className="gap-2 w-full sm:w-auto">
                    <ListingSubmitLink>
                        <Plus className="w-4 h-4" />
                        Новое объявление
                    </ListingSubmitLink>
                </Button>
            </div>

            <div className="flex flex-wrap gap-2 mb-4">
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

            <form
                className="flex flex-col sm:flex-row gap-2 mb-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    setQ(searchInput.trim());
                }}
            >
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                        value={searchInput}
                        onChange={(event) => setSearchInput(event.target.value)}
                        placeholder="Поиск по названию или улице"
                        className="pl-9 h-10"
                    />
                </div>
                <Button type="submit" variant="outline">Найти</Button>
                {canBulkArchive && selectedIds.length > 0 && (
                    <Button
                        type="button"
                        variant="secondary"
                        className="gap-2"
                        onClick={handleBulkArchive}
                        disabled={archivePropertyMutation.isPending}
                    >
                        <Archive className="w-4 h-4" />
                        Скрыть выбранные ({selectedIds.length})
                    </Button>
                )}
            </form>

            {isLoading ? (
                <div className="space-y-3">
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="h-28 bg-card rounded-xl shadow-card animate-pulse" />
                    ))}
                </div>
            ) : properties.length === 0 ? (
                <div className="text-center py-16 bg-card rounded-xl shadow-card">
                    {q || (data?.meta.counts?.all ?? 0) > 0 ? (
                        <p className="text-muted-foreground">
                            В этом статусе объявлений нет
                        </p>
                    ) : (
                        <>
                            <p className="text-muted-foreground mb-4">У вас пока нет объявлений</p>
                            <Button asChild className="gap-2">
                                <ListingSubmitLink>
                                    <Plus className="w-4 h-4" />
                                    Создать первое объявление
                                </ListingSubmitLink>
                            </Button>
                        </>
                    )}
                </div>
            ) : (
                <>
                    <div className="space-y-3">
                        {properties.map((property) => (
                            <ListingCard
                                key={property.id}
                                property={property}
                                showPublicLinks={activeStatus !== 'inactive'}
                                onRequestDelete={setDeleteTargetId}
                                selected={selectedIds.includes(property.id)}
                                onToggleSelect={canBulkArchive ? toggleSelected : undefined}
                            />
                        ))}
                    </div>
                    {pageCount > 1 && (
                        <div className="flex items-center justify-center gap-2 mt-6">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={page <= 1}
                                onClick={() => setPage((current) => Math.max(1, current - 1))}
                            >
                                Назад
                            </Button>
                            <span className="text-sm text-muted-foreground">
                                {page} / {pageCount}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={page >= pageCount}
                                onClick={() => setPage((current) => Math.min(pageCount, current + 1))}
                            >
                                Вперёд
                            </Button>
                        </div>
                    )}
                </>
            )}
        </motion.div>
    );
}
