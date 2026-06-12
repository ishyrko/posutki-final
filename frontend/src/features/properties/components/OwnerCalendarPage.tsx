'use client';

import { useEffect, useMemo, useState } from 'react';
import Link from 'next/link';
import { format, parseISO } from 'date-fns';
import { ru } from 'date-fns/locale';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Copy,
    ExternalLink,
    Link2,
    Loader2,
    RefreshCw,
    Trash2,
} from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    useCreateAvailabilityBlock,
    useDeleteAvailabilityBlock,
    useMyProperties,
    useOwnerCalendar,
    useRegenerateCalendarExportToken,
} from '@/features/properties/hooks';
import type { AvailabilityBlock } from '@/features/properties/api';
import { blockedDateKeySet } from '@/features/properties/property-calendar-utils';
import { cn } from '@/lib/utils';

const daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
const monthNames = [
    'Январь',
    'Февраль',
    'Март',
    'Апрель',
    'Май',
    'Июнь',
    'Июль',
    'Август',
    'Сентябрь',
    'Октябрь',
    'Ноябрь',
    'Декабрь',
];

function getDaysInMonth(year: number, month: number) {
    return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfWeek(year: number, month: number) {
    const day = new Date(year, month, 1).getDay();
    return day === 0 ? 6 : day - 1;
}

function dateKey(year: number, month: number, day: number) {
    return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function formatDateLabel(value: string) {
    try {
        return format(parseISO(value), 'd MMMM yyyy', { locale: ru });
    } catch {
        return value;
    }
}

function blocksForDate(blocks: AvailabilityBlock[], key: string) {
    return blocks.filter((block) => block.start <= key && key <= block.end);
}

export function OwnerCalendarPage() {
    const today = new Date();
    const [year, setYear] = useState(today.getFullYear());
    const [month, setMonth] = useState(today.getMonth());
    const [selectedPropertyId, setSelectedPropertyId] = useState<number>(0);
    const [rangeStart, setRangeStart] = useState<string | null>(null);
    const [selectedDate, setSelectedDate] = useState(format(today, 'yyyy-MM-dd'));
    const [blockNote, setBlockNote] = useState('');

    const { data: myProperties, isLoading: isLoadingProperties } = useMyProperties(1, 100);
    const dailyProperties = useMemo(
        () => (myProperties?.data ?? []).filter((property) => property.dealType === 'daily'),
        [myProperties?.data],
    );

    useEffect(() => {
        if (selectedPropertyId > 0) {
            return;
        }
        const first = dailyProperties[0];
        if (first) {
            setSelectedPropertyId(first.id);
        }
    }, [dailyProperties, selectedPropertyId]);

    const { data: calendar, isLoading: isLoadingCalendar, isError } = useOwnerCalendar(selectedPropertyId);
    const createBlock = useCreateAvailabilityBlock();
    const deleteBlock = useDeleteAvailabilityBlock();
    const regenerateToken = useRegenerateCalendarExportToken();

    const manualKeys = useMemo(
        () => blockedDateKeySet(calendar?.manualBlocks.map((block) => ({ start: block.start, end: block.end })) ?? []),
        [calendar?.manualBlocks],
    );
    const importedKeys = useMemo(
        () => blockedDateKeySet(calendar?.importedBlockedRanges ?? []),
        [calendar?.importedBlockedRanges],
    );

    const daysInMonth = getDaysInMonth(year, month);
    const firstDay = getFirstDayOfWeek(year, month);
    const days: (number | null)[] = [
        ...Array(firstDay).fill(null),
        ...Array.from({ length: daysInMonth }, (_, index) => index + 1),
    ];

    const selectedBlocks = blocksForDate(calendar?.manualBlocks ?? [], selectedDate);
    const selectedImported = calendar?.importedBlockedRanges.some(
        (range) => range.start <= selectedDate && selectedDate <= range.end,
    );

    const prevMonth = () => {
        if (month === 0) {
            setMonth(11);
            setYear((value) => value - 1);
            return;
        }
        setMonth((value) => value - 1);
    };

    const nextMonth = () => {
        if (month === 11) {
            setMonth(0);
            setYear((value) => value + 1);
            return;
        }
        setMonth((value) => value + 1);
    };

    const handleDayClick = (key: string) => {
        setSelectedDate(key);

        if (!rangeStart) {
            setRangeStart(key);
            return;
        }

        const start = rangeStart <= key ? rangeStart : key;
        const end = rangeStart <= key ? key : rangeStart;

        createBlock.mutate(
            {
                propertyId: selectedPropertyId,
                startDate: start,
                endDate: end,
                note: blockNote.trim() || undefined,
            },
            {
                onSuccess: () => {
                    toast.success('Даты заблокированы');
                    setRangeStart(null);
                    setBlockNote('');
                },
                onError: (error: Error) => {
                    toast.error(error.message || 'Не удалось заблокировать даты');
                },
            },
        );
    };

    const copyExportUrl = async () => {
        if (!calendar?.exportUrl) {
            return;
        }

        try {
            await navigator.clipboard.writeText(calendar.exportUrl);
            toast.success('Ссылка скопирована');
        } catch {
            toast.error('Не удалось скопировать ссылку');
        }
    };

    if (isLoadingProperties) {
        return (
            <div className="flex items-center justify-center py-20 text-muted-foreground">
                <Loader2 className="h-5 w-5 animate-spin mr-2" />
                Загрузка объявлений...
            </div>
        );
    }

    if (dailyProperties.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CalendarDays className="h-5 w-5" />
                        Календарь занятости
                    </CardTitle>
                    <CardDescription>
                        Календарь доступен для объявлений посуточной аренды. Создайте или опубликуйте такое объявление,
                        чтобы управлять датами.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button asChild>
                        <Link href="/razmestit/">Разместить объявление</Link>
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6 min-w-0 pb-24 lg:pb-0">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Календарь занятости</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Блокируйте даты вручную и публикуйте ссылку на календарь на других площадках.
                    </p>
                </div>
                <div className="w-full lg:max-w-sm">
                    <Label htmlFor="property-select" className="mb-2 block">
                        Объявление
                    </Label>
                    <Select
                        value={selectedPropertyId > 0 ? String(selectedPropertyId) : undefined}
                        onValueChange={(value) => {
                            setSelectedPropertyId(Number(value));
                            setRangeStart(null);
                        }}
                    >
                        <SelectTrigger id="property-select">
                            <SelectValue placeholder="Выберите объявление" />
                        </SelectTrigger>
                        <SelectContent>
                            {dailyProperties.map((property) => (
                                <SelectItem key={property.id} value={String(property.id)}>
                                    {property.title}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-5 gap-6">
                <Card className="xl:col-span-3 shadow-card">
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between gap-3">
                            <button type="button" onClick={prevMonth} className="p-1.5 rounded-lg hover:bg-muted">
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <CardTitle className="text-base">
                                {monthNames[month]} {year}
                            </CardTitle>
                            <button type="button" onClick={nextMonth} className="p-1.5 rounded-lg hover:bg-muted">
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                        <CardDescription>
                            {rangeStart
                                ? `Выберите конечную дату (начало: ${formatDateLabel(rangeStart)})`
                                : 'Нажмите дату, затем вторую — чтобы заблокировать период'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {isLoadingCalendar ? (
                            <div className="flex items-center justify-center py-16 text-muted-foreground">
                                <Loader2 className="h-5 w-5 animate-spin mr-2" />
                                Загрузка календаря...
                            </div>
                        ) : isError ? (
                            <p className="text-sm text-destructive py-8 text-center">Не удалось загрузить календарь</p>
                        ) : (
                            <>
                                <div className="mb-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                                    <span className="inline-flex items-center gap-2">
                                        <span className="h-3 w-3 rounded bg-primary" />
                                        Заблокировано вами
                                    </span>
                                    <span className="inline-flex items-center gap-2">
                                        <span className="h-3 w-3 rounded bg-amber-500/70" />
                                        Импорт с внешних площадок
                                    </span>
                                </div>
                                <div className="grid grid-cols-7 gap-1">
                                    {daysOfWeek.map((day) => (
                                        <div key={day} className="text-center text-xs text-muted-foreground py-2 font-medium">
                                            {day}
                                        </div>
                                    ))}
                                    {days.map((day, index) => {
                                        if (!day) {
                                            return <div key={`empty-${index}`} />;
                                        }

                                        const key = dateKey(year, month, day);
                                        const isManual = manualKeys.has(key);
                                        const isImported = importedKeys.has(key);
                                        const isSelected = key === selectedDate;
                                        const isRangeStart = key === rangeStart;
                                        const isToday =
                                            day === today.getDate() &&
                                            month === today.getMonth() &&
                                            year === today.getFullYear();

                                        return (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => handleDayClick(key)}
                                                disabled={createBlock.isPending}
                                                className={cn(
                                                    'relative aspect-square flex flex-col items-center justify-center rounded-lg text-sm transition-colors',
                                                    isSelected && 'ring-2 ring-primary ring-offset-2',
                                                    isRangeStart && 'bg-primary/20 text-primary font-semibold',
                                                    !isSelected && !isRangeStart && isManual && 'bg-primary text-primary-foreground',
                                                    !isSelected && !isRangeStart && !isManual && isImported && 'bg-amber-500/20 text-amber-900 dark:text-amber-100',
                                                    !isSelected && !isRangeStart && !isManual && !isImported && isToday && 'bg-primary/10 text-primary font-semibold',
                                                    !isSelected && !isRangeStart && !isManual && !isImported && !isToday && 'hover:bg-muted text-foreground',
                                                )}
                                            >
                                                {day}
                                            </button>
                                        );
                                    })}
                                </div>
                                <div className="mt-4 space-y-2">
                                    <Label htmlFor="block-note">Комментарий к новой блокировке (необязательно)</Label>
                                    <Input
                                        id="block-note"
                                        value={blockNote}
                                        onChange={(event) => setBlockNote(event.target.value)}
                                        placeholder="Например: личные планы"
                                    />
                                    {rangeStart && (
                                        <Button variant="outline" size="sm" onClick={() => setRangeStart(null)}>
                                            Отменить выбор периода
                                        </Button>
                                    )}
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                <div className="xl:col-span-2 space-y-6">
                    <Card className="shadow-card">
                        <CardHeader>
                            <CardTitle className="text-base">{formatDateLabel(selectedDate)}</CardTitle>
                            <CardDescription>Блокировки и занятость на выбранный день</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {selectedBlocks.length === 0 && !selectedImported && (
                                <p className="text-sm text-muted-foreground">На этот день нет занятости</p>
                            )}
                            {selectedImported && (
                                <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm">
                                    Занято по данным внешнего календаря (только просмотр)
                                </div>
                            )}
                            {selectedBlocks.map((block) => (
                                <div key={block.id} className="rounded-lg border border-border p-3 space-y-2">
                                    <div className="text-sm font-medium">
                                        {formatDateLabel(block.start)}
                                        {block.end !== block.start ? ` — ${formatDateLabel(block.end)}` : ''}
                                    </div>
                                    {block.note && <p className="text-sm text-muted-foreground">{block.note}</p>}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-destructive hover:text-destructive"
                                        disabled={deleteBlock.isPending}
                                        onClick={() =>
                                            deleteBlock.mutate(
                                                { propertyId: selectedPropertyId, blockId: block.id },
                                                {
                                                    onSuccess: () => toast.success('Блокировка снята'),
                                                    onError: () => toast.error('Не удалось снять блокировку'),
                                                },
                                            )
                                        }
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Снять блокировку
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="shadow-card">
                        <CardHeader>
                            <CardTitle className="text-base flex items-center gap-2">
                                <Link2 className="h-4 w-4" />
                                Экспорт календаря
                            </CardTitle>
                            <CardDescription>
                                Постоянная ссылка для Booking, Airbnb, Kufar и других площадок
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="rounded-lg border border-border bg-muted/40 p-3 text-sm break-all">
                                {calendar?.exportUrl || '—'}
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={copyExportUrl} disabled={!calendar?.exportUrl}>
                                    <Copy className="h-4 w-4 mr-2" />
                                    Копировать
                                </Button>
                                {calendar?.exportUrl && (
                                    <Button variant="outline" size="sm" asChild>
                                        <a href={calendar.exportUrl} target="_blank" rel="noreferrer">
                                            <ExternalLink className="h-4 w-4 mr-2" />
                                            Открыть
                                        </a>
                                    </Button>
                                )}
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={regenerateToken.isPending}
                                    onClick={() =>
                                        regenerateToken.mutate(selectedPropertyId, {
                                            onSuccess: () => toast.success('Ссылка обновлена'),
                                            onError: () => toast.error('Не удалось обновить ссылку'),
                                        })
                                    }
                                >
                                    <RefreshCw className={cn('h-4 w-4 mr-2', regenerateToken.isPending && 'animate-spin')} />
                                    Обновить ссылку
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-card">
                        <CardHeader>
                            <CardTitle className="text-base">Синхронизация импорта</CardTitle>
                            <CardDescription>
                                Внешние календари настраиваются в{' '}
                                <Link href={`/kabinet/redaktirovat/${selectedPropertyId}/`} className="text-primary hover:underline">
                                    редактировании объявления
                                </Link>
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <p className="text-muted-foreground">
                                Последняя синхронизация:{' '}
                                {calendar?.externalCalendarSyncedAt
                                    ? format(parseISO(calendar.externalCalendarSyncedAt), 'd MMM yyyy, HH:mm', { locale: ru })
                                    : 'ещё не выполнялась'}
                            </p>
                            {(calendar?.externalCalendarUrls ?? []).length > 0 ? (
                                <ul className="space-y-2">
                                    {calendar?.externalCalendarUrls.map((url) => (
                                        <li key={url} className="rounded-lg border border-border px-3 py-2 break-all">
                                            {url}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground">Внешние календари не подключены</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
