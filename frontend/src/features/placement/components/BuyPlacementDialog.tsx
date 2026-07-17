'use client';

import { useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { BynCurrencyMark } from '@/components/BynCurrency';
import { Property } from '@/features/properties/types';
import {
    useCreatePlacementPurchase,
    usePlacementLevels,
    usePlacementPurchaseQuote,
    usePlacementScope,
} from '@/features/placement/hooks';
import {
    calcBoostPriceByn,
    isPlacementBoostActive,
    PLACEMENT_DURATIONS,
    type PlacementTariffScope,
} from '@/features/placement/types';
import { cn } from '@/lib/utils';

function getApiErrorMessage(error: unknown, fallback: string): string {
    if (error && typeof error === 'object' && 'response' in error) {
        const response = (error as { response?: { data?: { message?: string; error?: string } } }).response;
        const message = response?.data?.message ?? response?.data?.error;
        if (typeof message === 'string' && message.trim() !== '') {
            return message;
        }
    }
    if (error instanceof Error && error.message) {
        return error.message;
    }
    return fallback;
}

function formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

type Mode = 'level' | 'boost';

interface BuyPlacementDialogProps {
    property: Property;
    open: boolean;
    mode: Mode;
    onOpenChange: (open: boolean) => void;
}

export function BuyPlacementDialog({
    property,
    open,
    mode,
    onOpenChange,
}: BuyPlacementDialogProps) {
    const router = useRouter();
    const isHouse = property.type === 'house';
    const tariffScope = useMemo((): PlacementTariffScope | null => {
        if (isHouse) {
            const regionId = property.address?.regionId;
            return regionId && regionId > 0
                ? { propertyType: 'house', regionId }
                : null;
        }
        const cityId = property.address?.cityId;
        return cityId && cityId > 0 ? { propertyType: 'apartment', cityId } : null;
    }, [isHouse, property.address?.cityId, property.address?.regionId]);
    const locationLabel = isHouse
        ? property.address?.regionName ?? 'области'
        : property.address?.cityName ?? 'города';
    const { data: levels = [], isLoading: levelsLoading } = usePlacementLevels(tariffScope);
    const { data: scopeSettings } = usePlacementScope(tariffScope);
    const create = useCreatePlacementPurchase();

    const [level, setLevel] = useState<number | null>(null);
    const [durationMonths, setDurationMonths] = useState<number>(1);

    const currentLevel = property.placementIsTrial ? 0 : property.placementBaseLevel ?? 0;

    const selectedLevel = useMemo(
        () => levels.find((item) => item.level === level) ?? null,
        [levels, level],
    );

    const {
        data: quote,
        isLoading: quoteLoading,
        isError: quoteError,
        error: quoteQueryError,
    } = usePlacementPurchaseQuote(
        property.id,
        mode === 'level' ? level : null,
        durationMonths,
        open && mode === 'level',
    );

    const maxLevel = scopeSettings?.maxLevel ?? 5;
    const baseLevel = property.placementBaseLevel ?? 0;
    const boostActive = isPlacementBoostActive(property.placementBoostExpiresAt);
    const boostPriceByn = useMemo(
        () => (baseLevel < maxLevel ? calcBoostPriceByn(baseLevel, levels) : null),
        [baseLevel, maxLevel, levels],
    );
    const canBuyBoost = mode === 'boost' && baseLevel < maxLevel && boostPriceByn != null;

    const isUpgradeMode = quote?.mode === 'upgrade';
    const isRenewalMode = quote?.mode === 'renewal';

    const total =
        mode === 'boost'
            ? boostPriceByn
            : quote != null
              ? quote.priceByn
              : selectedLevel != null
                ? selectedLevel.priceBynPerMonth * durationMonths
                : null;

    const canSubmit =
        !create.isPending &&
        !quoteLoading &&
        (mode === 'boost'
            ? canBuyBoost
            : durationMonths > 0 &&
              selectedLevel != null &&
              !quoteError &&
              (selectedLevel.available == null || selectedLevel.available > 0) &&
              selectedLevel.level >= currentLevel);

    const handleSubmit = () => {
        if (!canSubmit) return;
        create.mutate(
            mode === 'boost'
                ? {
                      propertyId: property.id,
                      kind: 'boost',
                  }
                : {
                      propertyId: property.id,
                      kind: 'level',
                      level: level!,
                      durationMonths,
                  },
            {
                onSuccess: (purchase) => {
                    onOpenChange(false);
                    toast.success('Заявка создана');
                    router.push(`/kabinet/oplata/${purchase.id}/`);
                },
                onError: (e) =>
                    toast.error(getApiErrorMessage(e, 'Не удалось создать заявку')),
            },
        );
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    setLevel(null);
                    setDurationMonths(1);
                }
                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'boost' ? 'Купить VIP-буст на 24 часа' : 'Купить VIP-размещение'}
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        {property.title}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    <div className="rounded-lg border border-border bg-muted/40 px-3 py-2.5">
                        <p className="text-sm font-medium text-foreground leading-snug">
                            {property.title}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            VIP-ротация не гарантирует фиксированное место, но показывает объявление
                            выше более низких уровней.
                        </p>
                    </div>

                    {mode === 'level' && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-foreground">VIP-уровень</p>
                            {levelsLoading ? (
                                <p className="text-sm text-muted-foreground">Загрузка…</p>
                            ) : levels.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Для {locationLabel} VIP-тарифы ещё не настроены.
                                </p>
                            ) : (
                                <div className="space-y-2 max-h-56 overflow-y-auto">
                                    {levels.map((item) => {
                                        const full =
                                            item.available != null && item.available <= 0;
                                        const isDowngrade =
                                            currentLevel > 0 && item.level < currentLevel;
                                        const disabled = full || isDowngrade;
                                        const isCurrentLevel = item.level === currentLevel;
                                        const isUpgrade = currentLevel > 0 && item.level > currentLevel;

                                        return (
                                            <button
                                                key={item.id}
                                                type="button"
                                                disabled={disabled}
                                                onClick={() => setLevel(item.level)}
                                                className={cn(
                                                    'w-full flex items-center justify-between gap-3 rounded-lg border p-3 text-left text-sm transition-colors',
                                                    disabled
                                                        ? 'opacity-50 cursor-not-allowed border-border'
                                                        : level === item.level
                                                          ? 'border-primary bg-primary/5'
                                                          : 'border-border hover:border-primary/40',
                                                )}
                                            >
                                                <div>
                                                    <p className="font-medium text-foreground">
                                                        {item.label}
                                                        {isCurrentLevel && currentLevel > 0 && (
                                                            <span className="ml-1.5 text-xs font-normal text-primary">
                                                                · продление
                                                            </span>
                                                        )}
                                                        {isUpgrade && (
                                                            <span className="ml-1.5 text-xs font-normal text-primary">
                                                                · апгрейд
                                                            </span>
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {isDowngrade
                                                            ? 'Уровень ниже текущего недоступен'
                                                            : full
                                                              ? 'Нет свободных мест'
                                                              : item.capacity != null
                                                                ? `Осталось ${item.available} из ${item.capacity}`
                                                                : 'Без ограничения по местам'}
                                                    </p>
                                                </div>
                                                <span className="font-semibold inline-flex items-baseline gap-1 shrink-0">
                                                    {item.priceBynPerMonth} <BynCurrencyMark />
                                                    <span className="text-xs font-normal text-muted-foreground">
                                                        /мес
                                                    </span>
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    )}

                    {mode === 'boost' && (
                        <div className="rounded-lg border border-border p-3 text-sm space-y-2">
                            {canBuyBoost ? (
                                <>
                                    <p className="inline-flex items-baseline gap-1">
                                        <span className="text-muted-foreground">Цена:</span>
                                        <span className="font-semibold text-foreground">
                                            {boostPriceByn}
                                        </span>
                                        <BynCurrencyMark />
                                        <span className="text-muted-foreground">за 24 часа</span>
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Временно повышает объявление на один VIP-уровень (до VIP{' '}
                                        {Math.min(baseLevel + 1, maxLevel)}).
                                        {boostActive
                                            ? ' Новая покупка продлит текущий буст на 24 часа после его окончания.'
                                            : ''}
                                    </p>
                                </>
                            ) : baseLevel >= maxLevel ? (
                                <p className="text-muted-foreground">
                                    Буст недоступен: объявление уже на максимальном VIP-уровне для этой
                                    локации.
                                </p>
                            ) : levelsLoading ? (
                                <p className="text-muted-foreground">Загрузка тарифов…</p>
                            ) : (
                                <p className="text-muted-foreground">
                                    Для {locationLabel} не задан тариф следующего VIP-уровня.
                                </p>
                            )}
                        </div>
                    )}

                    {mode === 'level' && isUpgradeMode && quote?.targetExpiresAt && (
                        <div className="rounded-lg border border-border p-3 text-sm space-y-1">
                            <p className="font-medium text-foreground">Апгрейд текущей подписки</p>
                            <p className="text-xs text-muted-foreground">
                                Доплата за разницу в цене до конца текущего срока. Действует до{' '}
                                {formatDateTime(quote.targetExpiresAt)} (как у текущей подписки).
                            </p>
                        </div>
                    )}

                    {mode === 'level' && !isUpgradeMode && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-foreground">Срок</p>
                            <Select
                                value={String(durationMonths)}
                                onValueChange={(v) => setDurationMonths(Number(v))}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {PLACEMENT_DURATIONS.map((m) => (
                                        <SelectItem key={m} value={String(m)}>
                                            {m}{' '}
                                            {m === 1 ? 'месяц' : m < 5 ? 'месяца' : 'месяцев'}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {isRenewalMode && quote?.targetExpiresAt && (
                                <p className="text-xs text-muted-foreground">
                                    Новый срок действия: до {formatDateTime(quote.targetExpiresAt)}
                                </p>
                            )}
                        </div>
                    )}

                    {mode === 'level' && quoteError && level != null && (
                        <p className="text-sm text-destructive">
                            {getApiErrorMessage(quoteQueryError, 'Не удалось рассчитать стоимость')}
                        </p>
                    )}

                    {total != null && !quoteError && (
                        <p className="text-sm text-foreground">
                            Итого:{' '}
                            <span className="font-bold inline-flex items-baseline gap-1">
                                {quoteLoading ? '…' : total} {!quoteLoading && <BynCurrencyMark />}
                            </span>
                            {isUpgradeMode && (
                                <span className="block text-xs text-muted-foreground mt-1">
                                    Доплата за апгрейд до конца текущего срока
                                </span>
                            )}
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Отмена
                    </Button>
                    <Button disabled={!canSubmit} onClick={handleSubmit}>
                        {create.isPending ? 'Создание…' : 'Перейти к оплате'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
