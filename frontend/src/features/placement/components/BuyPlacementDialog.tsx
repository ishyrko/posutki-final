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
    usePlacementSlots,
    useStandardPlacementPrice,
} from '@/features/placement/hooks';
import { PLACEMENT_DURATIONS, type PlacementTariffScope } from '@/features/placement/types';
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

type Mode = 'special' | 'standard';

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
    const { data: slots = [], isLoading: slotsLoading } = usePlacementSlots(
        mode === 'special' ? tariffScope : null,
    );
    const { data: standardPrice } = useStandardPlacementPrice(
        mode === 'standard' ? tariffScope : null,
    );
    const create = useCreatePlacementPurchase();

    const [slotId, setSlotId] = useState<number | null>(null);
    const [durationMonths, setDurationMonths] = useState<number>(1);

    const selectedSlot = useMemo(
        () => slots.find((s) => s.id === slotId) ?? null,
        [slots, slotId],
    );

    const pricePerMonth =
        mode === 'special'
            ? selectedSlot?.priceBynPerMonth ?? null
            : standardPrice?.priceBynPerMonth ?? null;
    const total =
        pricePerMonth != null ? pricePerMonth * durationMonths : null;

    const canSubmit =
        !create.isPending &&
        durationMonths > 0 &&
        (mode === 'standard'
            ? standardPrice != null
            : selectedSlot != null && selectedSlot.available > 0);

    const handleSubmit = () => {
        if (!canSubmit) return;
        create.mutate(
            {
                propertyId: property.id,
                type: mode,
                durationMonths,
                slotId: mode === 'special' ? slotId : null,
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
                    setSlotId(null);
                    setDurationMonths(1);
                }
                onOpenChange(next);
            }}
        >
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'special'
                            ? 'Купить топ-позицию'
                            : 'Оплатить стандартное размещение'}
                    </DialogTitle>
                    <DialogDescription>
                        {property.title}. Оплата помесячная — после создания заявки откроется
                        экран онлайн-оплаты.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    {mode === 'special' && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-foreground">Диапазон позиций</p>
                            {slotsLoading ? (
                                <p className="text-sm text-muted-foreground">Загрузка…</p>
                            ) : slots.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Для {locationLabel} объявления диапазоны ещё не настроены.
                                </p>
                            ) : (
                                <div className="space-y-2 max-h-56 overflow-y-auto">
                                    {slots.map((slot) => {
                                        const full = slot.available <= 0;
                                        return (
                                            <button
                                                key={slot.id}
                                                type="button"
                                                disabled={full}
                                                onClick={() => setSlotId(slot.id)}
                                                className={cn(
                                                    'w-full flex items-center justify-between gap-3 rounded-lg border p-3 text-left text-sm transition-colors',
                                                    full
                                                        ? 'opacity-50 cursor-not-allowed border-border'
                                                        : slotId === slot.id
                                                          ? 'border-primary bg-primary/5'
                                                          : 'border-border hover:border-primary/40',
                                                )}
                                            >
                                                <div>
                                                    <p className="font-medium text-foreground">
                                                        Позиция {slot.label}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {full
                                                            ? 'Нет свободных мест'
                                                            : `Свободно ${slot.available} из ${slot.capacity}`}
                                                    </p>
                                                </div>
                                                <span className="font-semibold inline-flex items-baseline gap-1 shrink-0">
                                                    {slot.priceBynPerMonth} <BynCurrencyMark />
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

                    {mode === 'standard' && (
                        <div className="rounded-lg border border-border p-3 text-sm">
                            {standardPrice ? (
                                <p className="inline-flex items-baseline gap-1">
                                    <span className="text-muted-foreground">Цена:</span>
                                    <span className="font-semibold text-foreground">
                                        {standardPrice.priceBynPerMonth}
                                    </span>
                                    <BynCurrencyMark />
                                    <span className="text-muted-foreground">/ мес</span>
                                </p>
                            ) : (
                                <p className="text-muted-foreground">
                                    Для {locationLabel} объявления цена стандартного размещения не задана.
                                </p>
                            )}
                        </div>
                    )}

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
                    </div>

                    {total != null && (
                        <p className="text-sm text-foreground">
                            Итого:{' '}
                            <span className="font-bold inline-flex items-baseline gap-1">
                                {total} <BynCurrencyMark />
                            </span>
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
