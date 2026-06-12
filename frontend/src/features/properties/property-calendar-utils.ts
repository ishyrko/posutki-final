import { format, parseISO, subDays } from 'date-fns';
import type { BlockedDateRange } from '@/features/properties/api';

export const CALENDAR_VISIBILITY_DAYS = 30;

/** Стили занятых дат в react-day-picker (классы вешаются на ячейку, зачёркивание — на кнопку). */
export const bookedDayModifierClassNames = {
    booked: [
        'bg-muted',
        '[&_button]:bg-muted',
        '[&_button]:text-muted-foreground',
        '[&_button]:line-through',
        '[&_button]:opacity-70',
        '[&_button]:hover:bg-muted',
        '[&_button]:cursor-not-allowed',
    ].join(' '),
};

export function isCalendarRecentlyActive(lastUpdatedAt: string | null | undefined): boolean {
    if (!lastUpdatedAt) {
        return false;
    }

    const updated = parseISO(lastUpdatedAt);
    if (Number.isNaN(updated.getTime())) {
        return false;
    }

    const threshold = subDays(new Date(), CALENDAR_VISIBILITY_DAYS);
    return updated.getTime() >= threshold.getTime();
}

export function expandBlockedRanges(ranges: BlockedDateRange[]): Date[] {
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

export function blockedDateKeySet(ranges: BlockedDateRange[]): Set<string> {
    return new Set(expandBlockedRanges(ranges).map((date) => format(date, 'yyyy-MM-dd')));
}

export function isBookedDate(date: Date, bookedKeys: Set<string>): boolean {
    return bookedKeys.has(format(date, 'yyyy-MM-dd'));
}

/** Занятые ночи в интервале [checkIn, checkOut) */
export function hasBookedNightInStay(
    checkIn: string,
    checkOut: string,
    bookedKeys: Set<string>,
): boolean {
    const start = new Date(`${checkIn}T00:00:00`);
    const end = new Date(`${checkOut}T00:00:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
        return false;
    }

    const cursor = new Date(start);
    while (cursor < end) {
        if (bookedKeys.has(format(cursor, 'yyyy-MM-dd'))) {
            return true;
        }
        cursor.setDate(cursor.getDate() + 1);
    }

    return false;
}

export function startOfToday(): Date {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
}
