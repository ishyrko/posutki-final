'use client';

import { useMemo, useState } from 'react';
import { useCities, useRegions } from '@/features/create-listing/hooks';
import { usePlacementLevels, usePlacementScope } from '@/features/placement/hooks';
import {
    calcBoostPriceByn,
    MAX_VISIBLE_PHOTOS_FREE_PLACEMENT,
    placementLevelLabel,
    type PlacementPropertyType,
    type PlacementTariffScope,
} from '@/features/placement/types';
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

    const { data: levelsData, isLoading: levelsLoading } = usePlacementLevels(tariffScope);
    const levels = levelsData?.levels ?? [];
    const { data: scopeSettings } = usePlacementScope(tariffScope);

    const maxLevel = scopeSettings?.maxLevel ?? 5;
    const boostPrices = useMemo(() => {
        const rows: { from: number; to: number; priceByn: number }[] = [];
        for (let from = 0; from < maxLevel; from += 1) {
            const priceByn = calcBoostPriceByn(from, levels);
            if (priceByn == null) {
                continue;
            }
            rows.push({ from, to: from + 1, priceByn });
        }
        return rows;
    }, [levels, maxLevel]);

    const locationLabel =
        propertyType === 'house'
            ? selectedRegion?.name ?? 'область'
            : selectedCity?.name ?? 'город';

    return (
        <div className="mx-auto max-w-3xl">
            <h1 className="font-display text-3xl font-bold text-foreground mb-3">
                VIP-тарифы размещения
            </h1>
            <p className="text-muted-foreground mb-8">
                Чем выше VIP-уровень, тем выше объявление в каталоге. Внутри одного уровня объявления
                ротируются — конкретная позиция не гарантируется. Один раз на аккаунт доступен
                бесплатный VIP 1 на месяц для одного объявления. При оплате на 3, 6 или 12 месяцев
                действует скидка 5%, 10% и 20%.
            </p>

            <div className="space-y-4 mb-10">
                <div className="rounded-xl border border-border bg-card p-5 shadow-card">
                    <h2 className="font-semibold text-foreground mb-2">VIP-уровни 1–5</h2>
                    <p className="text-sm text-muted-foreground">
                        Платное размещение с повышенным приоритетом в выдаче. Лимиты мест задаются
                        отдельно для каждого города и уровня — при заполнении покупка недоступна.
                        Срок оплаты: 1, 3, 6 или 12 месяцев. Скидки при покупке на срок: 3 мес. −5%,
                        6 мес. −10%, 12 мес. −20%.
                    </p>
                </div>

                <div className="rounded-xl border border-border bg-card p-5 shadow-card">
                    <h2 className="font-semibold text-foreground mb-2">VIP-буст на 24 часа</h2>
                    <p className="text-sm text-muted-foreground mb-3">
                        Временно повышает объявление на один VIP-уровень. На максимальном уровне буст
                        недоступен.
                    </p>
                    {boostPrices.length > 0 ? (
                        <ul className="space-y-1.5 text-sm">
                            {boostPrices.map((row) => (
                                <li
                                    key={`${row.from}-${row.to}`}
                                    className="flex items-baseline justify-between gap-3"
                                >
                                    <span className="text-muted-foreground">
                                        {placementLevelLabel(row.from)} → {placementLevelLabel(row.to)}
                                    </span>
                                    <span className="font-semibold text-foreground inline-flex items-baseline gap-1">
                                        {row.priceByn} <BynCurrencyMark />
                                        <span className="text-xs font-normal text-muted-foreground">
                                            / 24 ч
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Для выбранной локации тарифы VIP-уровней пока не заданы.
                        </p>
                    )}
                </div>

                <div
                    id="besplatnoe"
                    className="rounded-xl border border-border bg-card p-5 shadow-card scroll-mt-24"
                >
                    <h2 className="font-semibold text-foreground mb-2">Бесплатное размещение</h2>
                    <p className="text-sm text-muted-foreground">
                        После окончания бесплатного VIP 1 или оплаченного VIP объявление остаётся в
                        каталоге на бесплатном уровне с более низкой позицией. На публичной карточке
                        показываются не более {MAX_VISIBLE_PHOTOS_FREE_PLACEMENT} фотографий; видео,
                        Instagram и сайт скрыты. Все данные сохраняются и снова отображаются после
                        перехода на VIP.
                    </p>
                </div>

                <div className="rounded-xl border border-primary/30 bg-primary/5 p-5">
                    <h2 className="font-semibold text-foreground mb-2 flex items-center gap-2">
                        <Check className="w-4 h-4 text-primary" />
                        Бесплатный VIP 1 на месяц
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Один раз на аккаунт: после первой публикации одно объявление автоматически
                        получает бесплатный VIP 1 на 1 месяц. Остальные объявления сразу на бесплатном
                        уровне.
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

            <p className="text-sm text-muted-foreground mb-3">
                Стоимость — {locationLabel}
                {scopeSettings?.maxLevel ? ` · макс. VIP ${scopeSettings.maxLevel}` : ''}
            </p>

            <div className="rounded-xl border border-border bg-card overflow-hidden shadow-card mb-6">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-border bg-muted/40 text-xs text-muted-foreground">
                            <th className="text-left py-3 px-4 font-medium">Уровень</th>
                            <th className="text-right py-3 px-4 font-medium">Цена</th>
                            <th className="text-right py-3 px-4 font-medium hidden sm:table-cell">
                                Места
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {levelsLoading ? (
                            <tr>
                                <td colSpan={3} className="py-8 text-center text-muted-foreground">
                                    Загрузка…
                                </td>
                            </tr>
                        ) : levels.length === 0 ? (
                            <tr>
                                <td colSpan={3} className="py-8 text-center text-muted-foreground">
                                    {propertyType === 'house'
                                        ? 'Для этой области VIP-тарифы ещё не настроены.'
                                        : 'Для этого города VIP-тарифы ещё не настроены.'}
                                </td>
                            </tr>
                        ) : (
                            levels.map((item) => (
                                <tr key={item.id} className="border-b border-border last:border-0">
                                    <td className="py-3 px-4 text-foreground font-medium">
                                        {item.label}
                                    </td>
                                    <td className="py-3 px-4 text-right font-semibold text-foreground">
                                        <span className="inline-flex items-baseline gap-1 justify-end">
                                            {item.priceBynPerMonth} <BynCurrencyMark />
                                            <span className="text-xs font-normal text-muted-foreground">
                                                /мес
                                            </span>
                                        </span>
                                    </td>
                                    <td className="py-3 px-4 text-right text-muted-foreground hidden sm:table-cell">
                                        {item.capacity != null
                                            ? `${item.occupied} / ${item.capacity}`
                                            : 'без лимита'}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
