'use client';

import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import {
    AREA_MAX,
    AREA_MIN,
    FLOOR_MAX,
    FLOOR_MIN,
    TOTAL_FLOORS_MAX,
    TOTAL_FLOORS_MIN,
} from '@/features/create-listing/validation';

export const listingParameterLabelClass =
    'text-sm font-semibold text-foreground mb-2 block font-display';

const pillBtnBase = 'px-3 py-1.5 rounded-lg text-sm font-medium transition-all';
const chipInactive = `${pillBtnBase} bg-surface border border-border text-foreground hover:bg-muted`;
const chipActive = `${pillBtnBase} bg-primary text-primary-foreground border border-primary`;

const inputClass =
    'w-full px-4 py-2.5 rounded-xl bg-surface border border-border text-sm text-foreground placeholder:text-muted-foreground/60 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all';

/** Ряд чипов: на десктопе в одну линию; на узком экране — горизонтальная прокрутка. */
const chipRowClass = 'flex min-w-0 flex-nowrap gap-1.5 overflow-x-auto pb-0.5';

const segmentInputClass =
    'w-full border-0 bg-transparent px-3 py-2.5 text-sm text-foreground outline-none placeholder:text-muted-foreground/60 focus-visible:bg-muted/40 rounded-none';

export function NumericPillRow({
    label,
    value,
    onChange,
    min,
    max,
    error,
    plusDiscrete = false,
    plusDiscretePlus = 'four',
}: {
    label: ReactNode;
    value: string;
    onChange: (v: string) => void;
    min: number;
    max: number;
    error?: string;
    /** Чип «4+» или «5+» без дополнительного поля. */
    plusDiscrete?: boolean;
    /** При plusDiscrete: числовые чипы до 3 + «4+», или до 4 + «5+». */
    plusDiscretePlus?: 'four' | 'five';
}) {
    const useFivePlusBand = plusDiscrete && plusDiscretePlus === 'five';
    const exactCap = plusDiscrete
        ? Math.min(useFivePlusBand ? 4 : 3, max)
        : Math.min(4, max);
    const plusThreshold = plusDiscrete ? exactCap + 1 : 5;
    const plusChipLabel = plusDiscrete ? (useFivePlusBand ? '5+' : '4+') : '5+';

    const n = value.trim() === '' ? NaN : Number(value);
    const lowPills: number[] = [];
    if (min <= 0 && max >= 0) lowPills.push(0);
    const startNum = min <= 0 ? 1 : min;
    for (let i = startNum; i <= exactCap; i++) {
        lowPills.push(i);
    }
    const hasPlus = max > exactCap;
    const activeLow =
        Number.isFinite(n) && n >= min && n <= exactCap ? Math.trunc(n) : null;
    const isPlusRange = hasPlus && Number.isFinite(n) && n >= plusThreshold && n <= max;

    return (
        <div>
            <label className={cn(listingParameterLabelClass, 'flex items-center gap-1.5 mb-2')}>{label}</label>
            <div className={chipRowClass}>
                {lowPills.map((opt) => (
                    <button
                        key={opt}
                        type="button"
                        onClick={() => onChange(String(opt))}
                        className={cn(activeLow === opt ? chipActive : chipInactive, 'shrink-0')}
                    >
                        {opt}
                    </button>
                ))}
                {hasPlus && (
                    <button
                        type="button"
                        onClick={() => {
                            if (!Number.isFinite(n) || n < plusThreshold) onChange(String(plusThreshold));
                        }}
                        className={cn(isPlusRange ? chipActive : chipInactive, 'shrink-0')}
                    >
                        {plusChipLabel}
                    </button>
                )}
            </div>
            {hasPlus && isPlusRange && !plusDiscrete && (
                <input
                    type="number"
                    min={plusThreshold}
                    max={max}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className={cn(inputClass, 'mt-2', error ? 'border-destructive' : '')}
                />
            )}
            {error ? <p className="text-xs text-destructive mt-1">{error}</p> : null}
        </div>
    );
}

export type BathroomTypeValue = 'combined' | 'separate' | 'two';

export function bathroomTypeFromForm(bathrooms: string, amenities: string[]): BathroomTypeValue {
    const b = Number(bathrooms);
    if (Number.isFinite(b) && b >= 2) return 'two';
    if (amenities.includes('bathroom_separate')) return 'separate';
    return 'combined';
}

export function applyBathroomTypeSelection(
    prevAmenities: string[],
    type: BathroomTypeValue,
): { bathrooms: string; amenities: string[] } {
    const base = prevAmenities.filter((a) => a !== 'bathroom_combined' && a !== 'bathroom_separate');
    if (type === 'two') return { bathrooms: '2', amenities: base };
    if (type === 'separate') return { bathrooms: '1', amenities: [...base, 'bathroom_separate'] };
    return { bathrooms: '1', amenities: [...base, 'bathroom_combined'] };
}

const bathroomChoices: { value: BathroomTypeValue; label: string }[] = [
    { value: 'combined', label: 'Совмещенный' },
    { value: 'separate', label: 'Раздельный' },
    { value: 'two', label: '2 санузла' },
];

export function BathroomTypeRow({
    label,
    value,
    onChange,
    error,
}: {
    label: ReactNode;
    value: BathroomTypeValue;
    onChange: (v: BathroomTypeValue) => void;
    error?: string;
}) {
    return (
        <div>
            <label className={cn(listingParameterLabelClass, 'flex items-center gap-1.5 mb-2')}>{label}</label>
            <div className={chipRowClass}>
                {bathroomChoices.map((opt) => (
                    <button
                        key={opt.value}
                        type="button"
                        onClick={() => onChange(opt.value)}
                        className={cn(value === opt.value ? chipActive : chipInactive, 'shrink-0')}
                    >
                        {opt.label}
                    </button>
                ))}
            </div>
            {error ? <p className="text-xs text-destructive mt-1">{error}</p> : null}
        </div>
    );
}

export function SegmentedAreaTripleRow({
    area,
    livingArea,
    kitchenArea,
    onAreaChange,
    onLivingAreaChange,
    onKitchenAreaChange,
    areaError,
    livingError,
    kitchenError,
}: {
    area: string;
    livingArea: string;
    kitchenArea: string;
    onAreaChange: (v: string) => void;
    onLivingAreaChange: (v: string) => void;
    onKitchenAreaChange: (v: string) => void;
    areaError?: string;
    livingError?: string;
    kitchenError?: string;
}) {
    const cellBorder = (hasRight: boolean) =>
        cn(hasRight && 'border-r border-border', 'flex min-h-0 min-w-0 flex-1 flex-col bg-surface');

    return (
        <div>
            <p className={listingParameterLabelClass}>Площадь квартиры</p>
            <div
                className={cn(
                    'flex overflow-hidden rounded-xl border border-border',
                    areaError && 'border-destructive',
                )}
            >
                <div className={cellBorder(true)}>
                    <input
                        type="number"
                        inputMode="decimal"
                        min={AREA_MIN}
                        max={AREA_MAX}
                        step={0.1}
                        value={area}
                        onChange={(e) => onAreaChange(e.target.value)}
                        placeholder="—"
                        aria-invalid={areaError ? true : undefined}
                        className={segmentInputClass}
                    />
                    <span className="px-2 pb-2 pt-0 text-center text-xs text-muted-foreground">
                        Общая<span className="text-destructive">*</span>
                    </span>
                </div>
                <div className={cellBorder(true)}>
                    <input
                        type="number"
                        inputMode="decimal"
                        step={0.1}
                        value={livingArea}
                        onChange={(e) => onLivingAreaChange(e.target.value)}
                        placeholder="—"
                        aria-invalid={livingError ? true : undefined}
                        className={segmentInputClass}
                    />
                    <span className="px-2 pb-2 pt-0 text-center text-xs text-muted-foreground">Жилая</span>
                </div>
                <div className={cellBorder(false)}>
                    <input
                        type="number"
                        inputMode="decimal"
                        step={0.1}
                        value={kitchenArea}
                        onChange={(e) => onKitchenAreaChange(e.target.value)}
                        placeholder="—"
                        aria-invalid={kitchenError ? true : undefined}
                        className={segmentInputClass}
                    />
                    <span className="px-2 pb-2 pt-0 text-center text-xs text-muted-foreground">Кухня</span>
                </div>
            </div>
            {(areaError || livingError || kitchenError) && (
                <p className="text-xs text-destructive mt-1">
                    {[areaError, livingError, kitchenError].filter(Boolean).join(' · ')}
                </p>
            )}
        </div>
    );
}

export function FloorTotalFloorsRow({
    floor,
    totalFloors,
    onFloorChange,
    onTotalFloorsChange,
    floorError,
    totalFloorsError,
}: {
    floor: string;
    totalFloors: string;
    onFloorChange: (v: string) => void;
    onTotalFloorsChange: (v: string) => void;
    floorError?: string;
    totalFloorsError?: string;
}) {
    return (
        <div>
            <label htmlFor="listing-floor" className={listingParameterLabelClass}>
                Этаж / этажей<span className="text-destructive">*</span>
            </label>
            <div className="flex min-w-0 flex-nowrap items-center gap-2 overflow-x-auto pb-0.5">
                <input
                    id="listing-floor"
                    type="number"
                    min={FLOOR_MIN}
                    max={FLOOR_MAX}
                    value={floor}
                    onChange={(e) => onFloorChange(e.target.value)}
                    placeholder="—"
                    aria-invalid={floorError ? true : undefined}
                    className={cn(
                        inputClass,
                        'w-[7.5rem] shrink-0 sm:w-32',
                        floorError ? 'border-destructive' : '',
                    )}
                />
                <span className="text-sm text-muted-foreground shrink-0 px-0.5">из</span>
                <input
                    id="listing-total-floors"
                    type="number"
                    min={TOTAL_FLOORS_MIN}
                    max={TOTAL_FLOORS_MAX}
                    value={totalFloors}
                    onChange={(e) => onTotalFloorsChange(e.target.value)}
                    placeholder="—"
                    aria-invalid={totalFloorsError ? true : undefined}
                    className={cn(
                        inputClass,
                        'w-[7.5rem] shrink-0 sm:w-32',
                        totalFloorsError ? 'border-destructive' : '',
                    )}
                />
            </div>
            {(floorError || totalFloorsError) && (
                <p className="text-xs text-destructive mt-1">
                    {[floorError, totalFloorsError].filter(Boolean).join(' · ')}
                </p>
            )}
        </div>
    );
}
