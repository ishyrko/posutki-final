'use client';

import { useMemo, useState } from 'react';
import { useCities, useRegions } from '@/features/create-listing/hooks';
import { usePlacementSlots, useStandardPlacementPrice } from '@/features/placement/hooks';
import type { PlacementPropertyType, PlacementTariffScope } from '@/features/placement/types';
import { BynCurrencyMark } from '@/components/BynCurrency';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Check } from 'lucide-react';

export function TariffsPageContent() {
    const [propertyType, setPropertyType] = useState<PlacementPropertyType>('apartment');
    const { data: cities = [], isLoading: citiesLoading } = useCities();
    const { data: regions = [], isLoading: regionsLoading } = useRegions();

    const minsk = useMemo(
        () => cities.find((c) => c.slug === 'minsk') ?? cities[0] ?? null,
        [cities],
    );
    const defaultRegion = useMemo(
        () => regions.find((r) => r.slug === 'minsk') ?? regions[0] ?? null,
        [regions],
    );

    const [cityId, setCityId] = useState<number | null>(null);
    const [regionId, setRegionId] = useState<number | null>(null);

    const selectedCityId = cityId ?? minsk?.id ?? null;
    const selectedRegionId = regionId ?? defaultRegion?.id ?? null;
    const selectedCity = cities.find((c) => c.id === selectedCityId) ?? minsk;
    const selectedRegion = regions.find((r) => r.id === selectedRegionId) ?? defaultRegion;

    const tariffScope = useMemo((): PlacementTariffScope | null => {
        if (propertyType === 'house') {
            return selectedRegionId ? { propertyType: 'house', regionId: selectedRegionId } : null;
        }
        return selectedCityId ? { propertyType: 'apartment', cityId: selectedCityId } : null;
    }, [propertyType, selectedCityId, selectedRegionId]);

    const { data: slots = [], isLoading: slotsLoading } = usePlacementSlots(tariffScope);
    const { data: standardPrice } = useStandardPlacementPrice(tariffScope);

    const locationLabel =
        propertyType === 'house'
            ? selectedRegion?.name ?? 'область'
            : selectedCity?.name ?? 'город';

    return (
        <div className="mx-auto max-w-3xl">
            <h1 className="font-display text-3xl font-bold text-foreground mb-3">
                Стоимость размещения
            </h1>
            <p className="text-muted-foreground mb-8">
                Чем выше тариф, тем выше объявление в каталоге. Один раз на аккаунт доступен
                бесплатный пробный месяц стандартного размещения для одного объявления.
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
                        ротацией. Первый месяц после публикации первого объявления на аккаунте —
                        бесплатно (пробный период). Остальные объявления сразу на бесплатном тарифе.
                    </p>
                    {standardPrice ? (
                        <p className="text-lg font-semibold text-foreground inline-flex items-baseline gap-1">
                            {standardPrice.priceBynPerMonth} <BynCurrencyMark />
                            <span className="text-sm font-normal text-muted-foreground">/ мес</span>
                        </p>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Для выбранного {propertyType === 'house' ? 'региона' : 'города'} цена
                            пока не задана.
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
                        Один раз на аккаунт: после первой публикации одно объявление автоматически
                        получает стандартное размещение на 1 месяц. Остальные объявления сразу
                        попадают на бесплатный тариф. По окончании триала без оплаты объявление
                        тоже переходит на бесплатный тариф с ограничениями.
                    </p>
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3 mb-4">
                <h2 className="font-semibold text-foreground text-lg">Цены</h2>
                <Tabs
                    value={propertyType}
                    onValueChange={(value) => setPropertyType(value as PlacementPropertyType)}
                >
                    <TabsList>
                        <TabsTrigger value="apartment">Квартиры</TabsTrigger>
                        <TabsTrigger value="house">Дома</TabsTrigger>
                    </TabsList>
                </Tabs>
                {propertyType === 'apartment' ? (
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
                ) : (
                    <Select
                        value={selectedRegionId ? String(selectedRegionId) : undefined}
                        onValueChange={(v) => setRegionId(Number(v))}
                        disabled={regionsLoading || regions.length === 0}
                    >
                        <SelectTrigger className="w-[220px] h-10">
                            <SelectValue placeholder="Выберите область" />
                        </SelectTrigger>
                        <SelectContent>
                            {regions.map((r) => (
                                <SelectItem key={r.id} value={String(r.id)}>
                                    {r.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            </div>

            <p className="text-sm text-muted-foreground mb-3">Стоимость — {locationLabel}</p>

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
                                    {propertyType === 'house'
                                        ? 'Для этой области диапазоны ещё не настроены.'
                                        : 'Для этого города диапазоны ещё не настроены.'}
                                </td>
                            </tr>
                        ) : (
                            slots.map((slot) => (
                                <tr key={slot.id} className="border-b border-border last:border-0">
                                    <td className="py-3 px-4 text-foreground font-medium">
                                        {slot.label}
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
