'use client';

import { useMemo } from 'react';
import { Loader2 } from 'lucide-react';
import { Calendar } from '@/components/ui/calendar';
import { usePropertyCalendar } from '@/features/properties/hooks';
import type { BlockedDateRange } from '@/features/properties/api';
import { cn } from '@/lib/utils';

type PropertyAvailabilityCalendarProps = {
    propertyId: number;
    className?: string;
};

function expandBlockedRanges(ranges: BlockedDateRange[]): Date[] {
    const dates: Date[] = [];

    for (const range of ranges) {
        const start = new Date(`${range.start}T00:00:00`);
        const end = new Date(`${range.end}T00:00:00`);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
            continue;
        }

        const cursor = new Date(start);
        while (cursor <= end) {
            dates.push(new Date(cursor));
            cursor.setDate(cursor.getDate() + 1);
        }
    }

    return dates;
}

function formatLastUpdatedAt(value: string): string {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export function PropertyAvailabilityCalendar({ propertyId, className }: PropertyAvailabilityCalendarProps) {
    const { data, isLoading, isError } = usePropertyCalendar(propertyId);

    const bookedDates = useMemo(
        () => expandBlockedRanges(data?.blockedRanges ?? []),
        [data?.blockedRanges],
    );

    return (
        <div className={cn('rounded-xl border border-border/50 bg-card p-4 max-w-sm', className)}>
            <h3 className="text-base font-semibold text-foreground mb-1">Занятость</h3>
            <p className="text-xs text-muted-foreground mb-4">
                Занятые даты синхронизируются с внешними календарями
            </p>

            {isLoading ? (
                <div className="flex items-center justify-center py-10">
                    <Loader2 className="w-6 h-6 animate-spin text-primary" />
                </div>
            ) : isError ? (
                <p className="text-sm text-muted-foreground py-4">
                    Не удалось загрузить календарь занятости
                </p>
            ) : (
                <>
                    <Calendar
                        mode="single"
                        disabled={bookedDates}
                        modifiers={{ booked: bookedDates }}
                        modifiersClassNames={{
                            booked: 'bg-muted text-muted-foreground line-through opacity-70',
                        }}
                        className="mx-auto"
                    />
                    {data?.lastUpdatedAt && (
                        <p className="text-xs text-muted-foreground mt-3 text-center">
                            Обновлено: {formatLastUpdatedAt(data.lastUpdatedAt)}
                        </p>
                    )}
                </>
            )}
        </div>
    );
}
