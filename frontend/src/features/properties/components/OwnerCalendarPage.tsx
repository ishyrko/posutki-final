'use client';

import { useMemo, useState } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { format, parseISO, differenceInCalendarDays } from 'date-fns';
import { ru } from 'date-fns/locale';
import {
    AlertTriangle,
    ArrowLeft,
    BarChart3,
    ChevronLeft,
    ChevronRight,
    Copy,
    Edit3,
    ExternalLink,
    Link2,
    Loader2,
    MapPin,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    useCreateAvailabilityBlock,
    useDeleteAvailabilityBlock,
    useOwnerCalendar,
    useProperty,
} from '@/features/properties/hooks';
import type { AvailabilityBlock } from '@/features/properties/api';
import { blockedDateKeySet } from '@/features/properties/property-calendar-utils';
import { buildPropertyUrlFromRegionName } from '@/features/catalog/slugs';
import { formatAddress } from '@/features/properties/types';
import { cn } from '@/lib/utils';

const monthNames = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
];
const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

const toISO = (date: Date) => format(date, 'yyyy-MM-dd');

type BlockStatus = 'booked' | 'blocked';

type UpcomingItem = {
    id: string;
    from: string;
    to: string;
    guest: string;
    note?: string | null;
    status: BlockStatus;
    kind: 'manual' | 'imported';
};

function nightsBetween(from: string, to: string): number {
    try {
        return differenceInCalendarDays(parseISO(to), parseISO(from)) + 1;
    } catch {
        return 1;
    }
}

function formatNightLabel(nights: number): string {
    if (nights === 1) return '1 ночь';
    if (nights >= 2 && nights <= 4) return `${nights} ночи`;
    return `${nights} ночей`;
}

function buildPersistedNote(status: BlockStatus, guest: string, note: string): string | undefined {
    if (status === 'booked') {
        const guestName = guest.trim() || 'Гость';
        const extra = note.trim();
        return extra ? `BOOKED:${guestName}|${extra}` : `BOOKED:${guestName}`;
    }

    return note.trim() || undefined;
}

function parsePersistedNote(note: string | null | undefined): {
    guest: string;
    detail: string | null;
    status: BlockStatus;
} {
    if (!note?.trim()) {
        return { guest: '—', detail: null, status: 'blocked' };
    }

    if (note.startsWith('BOOKED:')) {
        const body = note.slice('BOOKED:'.length);
        const pipeIndex = body.indexOf('|');
        if (pipeIndex === -1) {
            return { guest: body || 'Гость', detail: null, status: 'booked' };
        }

        return {
            guest: body.slice(0, pipeIndex) || 'Гость',
            detail: body.slice(pipeIndex + 1) || null,
            status: 'booked',
        };
    }

    return { guest: '—', detail: note, status: 'blocked' };
}

export function OwnerCalendarPage() {
    const params = useParams<{ id: string }>();
    const propertyId = Number(params?.id ?? 0);

    const { data: property, isLoading: isLoadingProperty } = useProperty(propertyId);
    const { data: calendar, isLoading: isLoadingCalendar, isError } = useOwnerCalendar(propertyId);
    const createBlock = useCreateAvailabilityBlock();
    const deleteBlock = useDeleteAvailabilityBlock();

    const today = useMemo(() => {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        return d;
    }, []);
    const todayISO = toISO(today);

    const [cursor, setCursor] = useState(() => new Date(today.getFullYear(), today.getMonth(), 1));
    const [selectedFrom, setSelectedFrom] = useState<string | null>(null);
    const [selectedTo, setSelectedTo] = useState<string | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formData, setFormData] = useState({
        from: '',
        to: '',
        guest: '',
        note: '',
        status: 'blocked' as BlockStatus,
    });

    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    const lastDay = new Date(year, month + 1, 0);
    const startOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    const totalCells = Math.ceil((startOffset + lastDay.getDate()) / 7) * 7;

    const manualKeys = useMemo(
        () => blockedDateKeySet(calendar?.manualBlocks.map((b) => ({ start: b.start, end: b.end })) ?? []),
        [calendar?.manualBlocks],
    );
    const importedKeys = useMemo(
        () => blockedDateKeySet(calendar?.importedBlockedRanges ?? []),
        [calendar?.importedBlockedRanges],
    );

    const manualBlockByDate = useMemo(() => {
        const map = new Map<string, AvailabilityBlock>();
        for (const block of calendar?.manualBlocks ?? []) {
            const cursorDate = parseISO(block.start);
            const endDate = parseISO(block.end);
            let current = cursorDate;
            while (current <= endDate) {
                map.set(format(current, 'yyyy-MM-dd'), block);
                current = new Date(current);
                current.setDate(current.getDate() + 1);
            }
        }
        return map;
    }, [calendar?.manualBlocks]);

    const stats = useMemo(() => {
        const daysInMonth = lastDay.getDate();
        let booked = 0;
        let blocked = 0;

        for (let d = 1; d <= daysInMonth; d++) {
            const iso = toISO(new Date(year, month, d));
            const isManual = manualKeys.has(iso);
            const isImported = importedKeys.has(iso);
            if (isImported) booked++;
            else if (isManual) blocked++;
        }

        const available = daysInMonth - booked - blocked;
        const occupancy = daysInMonth > 0 ? Math.round((booked / daysInMonth) * 100) : 0;
        const price = property?.price?.amount ?? 0;
        const revenue = booked * price;

        return { daysInMonth, booked, blocked, available, occupancy, revenue };
    }, [year, month, lastDay, manualKeys, importedKeys, property?.price?.amount]);

    const upcoming = useMemo(() => {
        const items: UpcomingItem[] = [];

        for (const block of calendar?.manualBlocks ?? []) {
            const parsed = parsePersistedNote(block.note);
            items.push({
                id: block.id,
                from: block.start,
                to: block.end,
                guest: parsed.guest,
                note: parsed.detail,
                status: parsed.status,
                kind: 'manual',
            });
        }

        for (const range of calendar?.importedBlockedRanges ?? []) {
            items.push({
                id: `imported-${range.start}-${range.end}`,
                from: range.start,
                to: range.end,
                guest: 'Внешний календарь',
                note: null,
                status: 'booked',
                kind: 'imported',
            });
        }

        return items
            .filter((item) => item.to >= todayISO)
            .sort((a, b) => a.from.localeCompare(b.from))
            .slice(0, 8);
    }, [calendar?.manualBlocks, calendar?.importedBlockedRanges, todayISO]);

    const cells: (Date | null)[] = [];
    for (let i = 0; i < startOffset; i++) cells.push(null);
    for (let d = 1; d <= lastDay.getDate(); d++) cells.push(new Date(year, month, d));
    while (cells.length < totalCells) cells.push(null);

    const inRange = (iso: string) => {
        if (!selectedFrom) return false;
        const to = selectedTo || selectedFrom;
        return iso >= selectedFrom && iso <= to;
    };

    const handleDayClick = (iso: string) => {
        if (iso < todayISO) return;
        if (manualKeys.has(iso) || importedKeys.has(iso)) return;

        if (!selectedFrom || (selectedFrom && selectedTo)) {
            setSelectedFrom(iso);
            setSelectedTo(null);
            return;
        }

        if (iso < selectedFrom) {
            setSelectedTo(selectedFrom);
            setSelectedFrom(iso);
        } else {
            setSelectedTo(iso);
        }
    };

    const openForm = () => {
        if (!selectedFrom) return;
        setFormData({
            from: selectedFrom,
            to: selectedTo || selectedFrom,
            guest: '',
            note: '',
            status: 'blocked',
        });
        setFormOpen(true);
    };

    const closeForm = () => {
        setFormOpen(false);
    };

    const submitForm = (event: React.FormEvent) => {
        event.preventDefault();
        if (!formData.from || !formData.to) return;

        createBlock.mutate(
            {
                propertyId,
                startDate: formData.from,
                endDate: formData.to,
                note: buildPersistedNote(formData.status, formData.guest, formData.note),
            },
            {
                onSuccess: () => {
                    toast.success(formData.status === 'booked' ? 'Бронь добавлена' : 'Даты закрыты');
                    closeForm();
                    setSelectedFrom(null);
                    setSelectedTo(null);
                },
                onError: (error: Error) => toast.error(error.message || 'Не удалось заблокировать даты'),
            },
        );
    };

    const copyExportUrl = async () => {
        if (!calendar?.exportUrl) return;
        try {
            await navigator.clipboard.writeText(calendar.exportUrl);
            toast.success('Ссылка скопирована');
        } catch {
            toast.error('Не удалось скопировать ссылку');
        }
    };

    const propertyHref = property
        ? buildPropertyUrlFromRegionName(
              property.type,
              property.id,
              property.address?.regionName,
              property.address?.citySlug,
          )
        : '#';

    const isLoading = isLoadingProperty || isLoadingCalendar;

    if (propertyId <= 0) {
        return (
            <div className="text-center py-20 space-y-4">
                <h2 className="font-display text-2xl font-bold text-foreground">Некорректный адрес</h2>
                <Button asChild>
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/">К моим объявлениям</Link>
                </Button>
            </div>
        );
    }

    if (!isLoadingProperty && property && property.dealType !== 'daily') {
        return (
            <div className="text-center py-20 space-y-4">
                <div className="w-20 h-20 rounded-full bg-destructive/10 flex items-center justify-center mx-auto">
                    <AlertTriangle className="h-9 w-9 text-destructive" />
                </div>
                <h2 className="font-display text-2xl font-bold text-foreground">Календарь недоступен</h2>
                <p className="text-sm text-muted-foreground">Календарь занятости есть только у объявлений посуточной аренды.</p>
                <Button asChild>
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/">К моим объявлениям</Link>
                </Button>
            </div>
        );
    }

    if (!isLoading && (isError || !property)) {
        return (
            <div className="text-center py-20 space-y-4">
                <div className="w-20 h-20 rounded-full bg-destructive/10 flex items-center justify-center mx-auto">
                    <AlertTriangle className="h-9 w-9 text-destructive" />
                </div>
                <h2 className="font-display text-2xl font-bold text-foreground">Объявление не найдено</h2>
                <Button asChild>
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/">К моим объявлениям</Link>
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-6 min-w-0 pb-24 lg:pb-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="flex items-center gap-3 min-w-0">
                    <Link
                        href="/kabinet/moi-obyavleniya/aktivnye/"
                        className="p-2 rounded-lg hover:bg-muted transition-colors shrink-0"
                        aria-label="Назад"
                    >
                        <ArrowLeft className="h-5 w-5 text-muted-foreground" />
                    </Link>
                    <div className="min-w-0">
                        <h1 className="font-display text-2xl font-bold text-foreground truncate">Календарь броней</h1>
                        <p className="text-sm text-muted-foreground truncate">
                            {property?.title || calendar?.propertyTitle || 'Загрузка...'}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href={`/kabinet/statistika/${propertyId}/`}>
                            <BarChart3 className="h-4 w-4 mr-2" />
                            Статистика
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={`/kabinet/redaktirovat/${propertyId}/`}>
                            <Edit3 className="h-4 w-4 mr-2" />
                            Редактировать
                        </Link>
                    </Button>
                </div>
            </div>

            {property && (
                <div className="bg-card rounded-2xl shadow-card overflow-hidden flex flex-col sm:flex-row">
                    <Link href={propertyHref} className="sm:w-56 shrink-0">
                        {property.images[0] ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                                src={property.images[0].thumbnailUrl || property.images[0].url}
                                alt={property.title}
                                className="w-full h-32 sm:h-full object-cover"
                            />
                        ) : (
                            <div className="w-full h-32 sm:h-full bg-muted flex items-center justify-center text-sm text-muted-foreground">
                                Нет фото
                            </div>
                        )}
                    </Link>
                    <div className="flex-1 p-5 flex flex-col justify-between gap-3">
                        <div>
                            <Link href={propertyHref} className="font-display font-semibold text-foreground hover:text-primary transition-colors">
                                {property.title}
                            </Link>
                            <div className="flex items-center gap-1 text-muted-foreground text-sm mt-1">
                                <MapPin className="h-3.5 w-3.5 shrink-0" />
                                <span className="truncate">{formatAddress(property.address)}</span>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3 text-sm">
                            <span className="font-display font-bold text-foreground">{property.price.amount}</span>
                            <span className="text-muted-foreground">{property.price.currency} / сутки</span>
                        </div>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                    { label: 'Загрузка', value: `${stats.occupancy}%`, color: 'text-primary' },
                    { label: 'Занято дней', value: stats.booked, color: 'text-foreground' },
                    { label: 'Свободно', value: stats.available, color: 'text-emerald-600' },
                    {
                        label: 'Доход (план)',
                        value: `${stats.revenue.toLocaleString('ru-RU')} ${property?.price.currency ?? 'BYN'}`,
                        color: 'text-foreground',
                    },
                ].map((item) => (
                    <div key={item.label} className="bg-card rounded-2xl shadow-card p-5">
                        <div className="text-sm text-muted-foreground">{item.label}</div>
                        <div className={cn('font-display text-2xl font-bold mt-1', item.color)}>{item.value}</div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="bg-card rounded-2xl shadow-card p-5 lg:col-span-2">
                    <div className="flex items-center justify-between mb-5 gap-3">
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setCursor(new Date(year, month - 1, 1))}
                                className="p-2 rounded-lg hover:bg-muted transition-colors"
                                aria-label="Предыдущий месяц"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <div className="font-display text-lg font-semibold text-foreground min-w-[180px] text-center">
                                {monthNames[month]} {year}
                            </div>
                            <button
                                type="button"
                                onClick={() => setCursor(new Date(year, month + 1, 1))}
                                className="p-2 rounded-lg hover:bg-muted transition-colors"
                                aria-label="Следующий месяц"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="button"
                            onClick={() => setCursor(new Date(today.getFullYear(), today.getMonth(), 1))}
                            className="text-sm text-primary hover:underline"
                        >
                            Сегодня
                        </button>
                    </div>

                    {isLoading ? (
                        <div className="flex items-center justify-center py-20 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin mr-2" />
                            Загрузка календаря...
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-7 gap-1 mb-2">
                                {weekDays.map((day) => (
                                    <div key={day} className="text-xs font-medium text-muted-foreground text-center py-1">
                                        {day}
                                    </div>
                                ))}
                            </div>

                            <div className="grid grid-cols-7 gap-1">
                                {cells.map((date, index) => {
                                    if (!date) {
                                        return <div key={`empty-${index}`} className="aspect-square" />;
                                    }

                                    const iso = toISO(date);
                                    const isManual = manualKeys.has(iso);
                                    const isImported = importedKeys.has(iso);
                                    const isPast = iso < todayISO;
                                    const isToday = iso === todayISO;
                                    const isSelected = inRange(iso);
                                    const manualBlock = manualBlockByDate.get(iso);

                                    const base =
                                        'aspect-square rounded-lg flex flex-col items-center justify-center text-sm relative transition-all';
                                    let cls = '';
                                    if (isPast) cls = 'text-muted-foreground/50 cursor-not-allowed';
                                    else if (isManual) cls = 'bg-muted text-muted-foreground line-through cursor-default';
                                    else if (isImported) cls = 'bg-primary/15 text-primary font-semibold cursor-default';
                                    else if (isSelected) cls = 'bg-primary text-primary-foreground font-semibold ring-2 ring-primary';
                                    else cls = 'hover:bg-muted text-foreground cursor-pointer';

                                    return (
                                        <button
                                            key={iso}
                                            type="button"
                                            onClick={() => handleDayClick(iso)}
                                            disabled={isPast || isManual || isImported || createBlock.isPending}
                                            className={cn(base, cls, isToday && 'ring-1 ring-accent')}
                                            title={
                                                manualBlock?.note
                                                    ? manualBlock.note
                                                    : isImported
                                                      ? 'Импорт с внешней площадки'
                                                      : iso
                                            }
                                        >
                                            <span>{date.getDate()}</span>
                                            {isImported && !isManual && (
                                                <span className="absolute bottom-1 w-1 h-1 rounded-full bg-primary" />
                                            )}
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="mt-5 pt-4 border-t border-border flex flex-wrap items-center justify-between gap-3">
                                <div className="flex flex-wrap items-center gap-4 text-xs">
                                    <span className="inline-flex items-center gap-1.5">
                                        <span className="w-3 h-3 rounded bg-primary/15" /> Бронь
                                    </span>
                                    <span className="inline-flex items-center gap-1.5">
                                        <span className="w-3 h-3 rounded bg-muted" /> Закрыто
                                    </span>
                                    <span className="inline-flex items-center gap-1.5">
                                        <span className="w-3 h-3 rounded bg-primary" /> Выбор
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    {selectedFrom && (
                                        <span className="text-sm text-muted-foreground">
                                            {selectedFrom}
                                            {selectedTo && selectedTo !== selectedFrom ? ` — ${selectedTo}` : ''}
                                        </span>
                                    )}
                                    <Button size="sm" disabled={!selectedFrom} onClick={openForm} className="gap-1.5">
                                        <Plus className="h-4 w-4" /> Добавить
                                    </Button>
                                </div>
                            </div>
                        </>
                    )}
                </div>

                <div className="bg-card rounded-2xl shadow-card p-5">
                    <h2 className="font-display text-lg font-semibold text-foreground mb-1">Ближайшие брони</h2>
                    <p className="text-sm text-muted-foreground mb-4">Подтверждённые и закрытые периоды</p>
                    {upcoming.length === 0 ? (
                        <div className="text-sm text-muted-foreground py-8 text-center">Нет предстоящих броней</div>
                    ) : (
                        <div className="space-y-3">
                            {upcoming.map((item) => {
                                const nights = nightsBetween(item.from, item.to);
                                return (
                                    <div
                                        key={item.id}
                                        className="flex items-start gap-3 p-3 rounded-xl bg-muted/40 hover:bg-muted transition-colors"
                                    >
                                        <div
                                            className={cn(
                                                'w-1 self-stretch rounded-full',
                                                item.status === 'booked' ? 'bg-primary' : 'bg-muted-foreground',
                                            )}
                                        />
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-foreground text-sm truncate">{item.guest}</span>
                                                <span
                                                    className={cn(
                                                        'text-[10px] px-1.5 py-0.5 rounded-full font-semibold',
                                                        item.status === 'booked'
                                                            ? 'bg-primary/10 text-primary'
                                                            : 'bg-muted text-muted-foreground',
                                                    )}
                                                >
                                                    {item.status === 'booked' ? 'Бронь' : 'Закрыто'}
                                                </span>
                                            </div>
                                            <div className="text-xs text-muted-foreground mt-0.5">
                                                {item.from} → {item.to} · {formatNightLabel(nights)}
                                            </div>
                                            {item.note && (
                                                <div className="text-xs text-muted-foreground/80 mt-0.5 truncate">{item.note}</div>
                                            )}
                                        </div>
                                        {item.kind === 'manual' && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    deleteBlock.mutate(
                                                        { propertyId, blockId: item.id },
                                                        {
                                                            onSuccess: () => toast.success('Блокировка снята'),
                                                            onError: () => toast.error('Не удалось снять блокировку'),
                                                        },
                                                    )
                                                }
                                                disabled={deleteBlock.isPending}
                                                className="p-1.5 rounded-lg hover:bg-destructive/10 text-muted-foreground hover:text-destructive transition-colors"
                                                aria-label="Удалить"
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-card rounded-2xl shadow-card p-5 space-y-4">
                    <div>
                        <h2 className="font-display text-lg font-semibold text-foreground inline-flex items-center gap-2">
                            <Link2 className="h-4 w-4" />
                            Экспорт календаря
                        </h2>
                        <p className="text-sm text-muted-foreground mt-1">
                            Постоянная ссылка для Booking, Airbnb, Kufar и других площадок
                        </p>
                    </div>
                    <div className="rounded-xl border border-border bg-muted/40 p-3 text-sm break-all">
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
                    </div>
                </div>

                <div className="bg-card rounded-2xl shadow-card p-5 space-y-4">
                    <div>
                        <h2 className="font-display text-lg font-semibold text-foreground">Синхронизация импорта</h2>
                        <p className="text-sm text-muted-foreground mt-1">
                            Внешние календари настраиваются в{' '}
                            <Link href={`/kabinet/redaktirovat/${propertyId}/`} className="text-primary hover:underline">
                                редактировании объявления
                            </Link>
                        </p>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Последнее обновление импорта:{' '}
                        {calendar?.externalCalendarSyncedAt
                            ? format(parseISO(calendar.externalCalendarSyncedAt), 'd MMM yyyy, HH:mm', { locale: ru })
                            : 'ещё не выполнялось'}
                    </p>
                    {(calendar?.externalCalendarUrls ?? []).length > 0 ? (
                        <ul className="space-y-2">
                            {calendar?.externalCalendarUrls.map((url) => (
                                <li key={url} className="rounded-xl border border-border px-3 py-2 text-sm break-all">
                                    {url}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">Внешние календари не подключены</p>
                    )}
                </div>
            </div>

            {formOpen && (
                <div className="fixed inset-0 z-50 bg-foreground/50 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in">
                    <div className="bg-card rounded-2xl shadow-elevated w-full max-w-md p-6 relative">
                        <button
                            type="button"
                            onClick={closeForm}
                            className="absolute top-4 right-4 p-1.5 rounded-lg hover:bg-muted transition-colors"
                            aria-label="Закрыть"
                        >
                            <X className="h-4 w-4" />
                        </button>
                        <h3 className="font-display text-xl font-bold text-foreground mb-1">Новая запись</h3>
                        <p className="text-sm text-muted-foreground mb-5">Добавить бронь или закрыть даты</p>
                        <form onSubmit={submitForm} className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="block-from">Заезд</Label>
                                    <Input
                                        id="block-from"
                                        type="date"
                                        value={formData.from}
                                        onChange={(event) => setFormData({ ...formData, from: event.target.value })}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="block-to">Выезд</Label>
                                    <Input
                                        id="block-to"
                                        type="date"
                                        value={formData.to}
                                        onChange={(event) => setFormData({ ...formData, to: event.target.value })}
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <Label>Тип</Label>
                                <div className="grid grid-cols-2 gap-2 mt-1.5">
                                    {(['booked', 'blocked'] as const).map((status) => (
                                        <button
                                            key={status}
                                            type="button"
                                            onClick={() => setFormData({ ...formData, status })}
                                            className={cn(
                                                'px-3 py-2 rounded-lg text-sm font-medium border transition-colors',
                                                formData.status === status
                                                    ? 'bg-primary text-primary-foreground border-primary'
                                                    : 'bg-card text-foreground border-border hover:bg-muted',
                                            )}
                                        >
                                            {status === 'booked' ? 'Бронь гостя' : 'Закрыть даты'}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            {formData.status === 'booked' && (
                                <div>
                                    <Label htmlFor="block-guest">Гость</Label>
                                    <Input
                                        id="block-guest"
                                        value={formData.guest}
                                        onChange={(event) => setFormData({ ...formData, guest: event.target.value })}
                                        placeholder="Имя гостя"
                                    />
                                </div>
                            )}
                            <div>
                                <Label htmlFor="block-note">Заметка</Label>
                                <Input
                                    id="block-note"
                                    value={formData.note}
                                    onChange={(event) => setFormData({ ...formData, note: event.target.value })}
                                    placeholder="Необязательно"
                                />
                            </div>
                            <div className="flex gap-2 pt-2">
                                <Button type="button" variant="outline" className="flex-1" onClick={closeForm}>
                                    Отмена
                                </Button>
                                <Button type="submit" className="flex-1" disabled={createBlock.isPending}>
                                    {createBlock.isPending ? 'Сохранение...' : 'Сохранить'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
