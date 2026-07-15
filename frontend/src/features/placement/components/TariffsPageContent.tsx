'use client';

import { useMemo, useState } from 'react';
import { useCities } from '@/features/create-listing/hooks';
import { usePlacementSlots, useStandardPlacementPrice } from '@/features/placement/hooks';
import { BynCurrencyMark } from '@/components/BynCurrency';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Check } from 'lucide-react';

export function TariffsPageContent() {
    const { data: cities = [], isLoading: citiesLoading } = useCities();
    const minsk = useMemo(
        () => cities.find((c) => c.slug === 'minsk') ?? cities[0] ?? null,
        [cities],
    );
    const [cityId, setCityId] = useState<number | null>(null);
    const selectedCityId = cityId ?? minsk?.id ?? null;
    const selectedCity = cities.find((c) => c.id === selectedCityId) ?? minsk;

    const { data: slots = [], isLoading: slotsLoading } = usePlacementSlots(selectedCityId);
    const { data: standardPrice } = useStandardPlacementPrice(selectedCityId);

    return (
        <div className="mx-auto max-w-3xl">
            <h1 className="font-display text-3xl font-bold text-foreground mb-3">
                Стоимость размещения
            </h1>
            <p className="text-muted-foreground mb-8">
                Чем выше тариф, тем выше объявление в каталоге. Новым объявлениям доступен
                бесплатный пробный месяц стандартного размещения.
            </p>

            <div className="space-y-4 mb-10">
                <div className="rounded-xl border border-border bg-card p-5 shadow-card">
                    <h2 className="font-semibold text-foreground mb-2">Спецразмещение</h2>
                    <p className="text-sm text-muted-foreground">
                        Фиксированные диапазоны позиций в верхней части каталога. Количество мест
                        ограничено — при заполнении диапазона покупка недоступна.
                    </p>
                </div>

                <div className="rounded-xl border border-border bg-card p-5 shadow-card">
                    <h2 className="font-semibold text-foreground mb-2">Стандартное размещение</h2>
                    <p className="text-sm text-muted-foreground mb-3">
                        Объявления показываются ниже спецразмещения, в общей выдаче с периодической
                        ротацией. Первый месяц после публикации — бесплатно (пробный период).
                    </p>
                    {standardPrice ? (
                        <p className="text-lg font-semibold text-foreground inline-flex items-baseline gap-1">
                            {standardPrice.priceBynPerMonth} <BynCurrencyMark />
                            <span className="text-sm font-normal text-muted-foreground">/ мес</span>
                        </p>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Для выбранного города цена пока не задана.
                        </p>
                    )}
                </div>

                <div className="rounded-xl border border-border bg-card p-5 shadow-card">
                    <h2 className="font-semibold text-foreground mb-2">Бесплатное размещение</h2>
                    <p className="text-sm text-muted-foreground">
                        Если пробный период или оплаченное стандартное размещение закончились,
                        объявление остаётся в каталоге на бесплатном тарифе с ограничениями и более
                        низкой позицией — ниже спецразмещения и стандартного.
                    </p>
                </div>

                <div className="rounded-xl border border-primary/30 bg-primary/5 p-5">
                    <h2 className="font-semibold text-foreground mb-2 flex items-center gap-2">
                        <Check className="w-4 h-4 text-primary" />
                        Бесплатный пробный месяц
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        После первой публикации объявление автоматически получает стандартное
                        размещение на 1 месяц. По окончании триала без оплаты оно переходит на
                        бесплатный тариф с ограничениями.
                    </p>
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3 mb-4">
                <h2 className="font-semibold text-foreground text-lg">Цены по городам</h2>
                <Select
                    value={selectedCityId ? String(selectedCityId) : undefined}
                    onValueChange={(v) => setCityId(Number(v))}
                    disabled={citiesLoading || cities.length === 0}
                >
                    <SelectTrigger className="w-[220px] h-10">
                        <SelectValue placeholder="Выберите город" />
                    </SelectTrigger>
                    <SelectContent>
                        {cities.map((c) => (
                            <SelectItem key={c.id} value={String(c.id)}>
                                {c.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {selectedCity && (
                <p className="text-sm text-muted-foreground mb-3">
                    Стоимость — {selectedCity.name}
                </p>
            )}

            <div className="rounded-xl border border-border bg-card overflow-hidden shadow-card mb-6">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-border bg-muted/40 text-xs text-muted-foreground">
                            <th className="text-left py-3 px-4 font-medium">Позиция</th>
                            <th className="text-right py-3 px-4 font-medium">Цена</th>
                            <th className="text-right py-3 px-4 font-medium hidden sm:table-cell">
                                Места
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {slotsLoading ? (
                            <tr>
                                <td colSpan={3} className="py-8 text-center text-muted-foreground">
                                    Загрузка…
                                </td>
                            </tr>
                        ) : slots.length === 0 ? (
                            <tr>
                                <td colSpan={3} className="py-8 text-center text-muted-foreground">
                                    Для этого города диапазоны ещё не настроены.
                                </td>
                            </tr>
                        ) : (
                            slots.map((slot) => (
                                <tr key={slot.id} className="border-b border-border last:border-0">
                                    <td className="py-3 px-4 text-foreground font-medium">
                                        {slot.label}
                                        {slot.isTopSlot ? (
                                            <span className="ml-2 text-[10px] uppercase tracking-wide text-amber-600 font-bold">
                                                топ
                                            </span>
                                        ) : null}
                                    </td>
                                    <td className="py-3 px-4 text-right font-semibold text-foreground">
                                        <span className="inline-flex items-baseline gap-1 justify-end">
                                            {slot.priceBynPerMonth} <BynCurrencyMark />
                                            <span className="text-xs font-normal text-muted-foreground">
                                                /мес
                                            </span>
                                        </span>
                                    </td>
                                    <td className="py-3 px-4 text-right text-muted-foreground hidden sm:table-cell">
                                        {slot.occupied} / {slot.capacity}
                                    </td>
                                </tr>
                            ))
                        )}
                        {standardPrice && (
                            <tr className="bg-muted/20">
                                <td className="py-3 px-4 text-foreground">Стандартное размещение</td>
                                <td className="py-3 px-4 text-right font-semibold text-foreground">
                                    <span className="inline-flex items-baseline gap-1 justify-end">
                                        {standardPrice.priceBynPerMonth} <BynCurrencyMark />
                                        <span className="text-xs font-normal text-muted-foreground">
                                            /мес
                                        </span>
                                    </span>
                                </td>
                                <td className="py-3 px-4 text-right text-muted-foreground hidden sm:table-cell">
                                    без лимита
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
