'use client';

import { useMemo, useState } from 'react';
import { ChevronDown, Loader2 } from 'lucide-react';
import { Calendar } from '@/components/ui/calendar';
import { buttonVariants } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { usePropertyCalendar } from '@/features/properties/hooks';
import { bookedDayModifierClassNames, expandBlockedRanges } from '@/features/properties/property-calendar-utils';
import { cn } from '@/lib/utils';

type PropertyAvailabilityCalendarProps = {
    propertyId: number;
    className?: string;
};

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

const mobileExpandedCalendarClassNames = {
    months: 'relative flex w-full flex-col sm:w-fit sm:flex-row space-y-4 sm:space-x-4 sm:space-y-0',
    month: 'w-full space-y-3 sm:space-y-4',
    weekdays: 'flex w-full',
    weekday: 'text-muted-foreground flex-1 rounded-md font-normal text-[0.8rem] sm:w-9 sm:flex-none',
    week: 'flex w-full mt-2',
    day: cn(
        'relative flex-1 p-0 text-center text-sm focus-within:relative focus-within:z-20 aspect-square sm:aspect-auto sm:h-9 sm:w-9 sm:flex-none',
        '[&:has([aria-selected])]:bg-accent [&:has([aria-selected].day-outside)]:bg-accent/50',
        '[&:has([aria-selected].day-range-end)]:rounded-r-md [&:has([aria-selected])]:rounded-md',
    ),
    day_button: cn(
        buttonVariants({ variant: 'ghost' }),
        'size-full min-h-10 p-0 font-normal aria-selected:opacity-100 sm:h-9 sm:w-9 sm:min-h-0',
    ),
};

export function PropertyAvailabilityCalendar({ propertyId, className }: PropertyAvailabilityCalendarProps) {
    const [open, setOpen] = useState(false);
    const { data, isLoading, isError } = usePropertyCalendar(propertyId, open);

    const bookedDates = useMemo(
        () => expandBlockedRanges(data?.blockedRanges ?? []),
        [data?.blockedRanges],
    );

    return (
        <Collapsible
            open={open}
            onOpenChange={setOpen}
            className={cn('w-full rounded-2xl border border-border/50 bg-card shadow-card', className)}
        >
            <CollapsibleTrigger asChild>
                <button
                    type="button"
                    className="flex w-full cursor-pointer items-center justify-between gap-3 p-4 text-left transition-colors hover:bg-muted/30 rounded-2xl"
                >
                    <div className="min-w-0">
                        <h3 className="text-base font-semibold text-foreground">Календарь занятости</h3>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            {open
                                ? 'Актуальные занятые даты по данным владельца'
                                : 'Показать календарь занятости'}
                        </p>
                    </div>
                    <ChevronDown
                        className={cn(
                            'h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200',
                            open && 'rotate-180',
                        )}
                    />
                </button>
            </CollapsibleTrigger>

            <CollapsibleContent className="overflow-hidden px-2 pb-4 sm:px-4">
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
                            modifiersClassNames={bookedDayModifierClassNames}
                            className="w-full p-2 sm:w-fit sm:p-3 sm:mx-auto"
                            classNames={mobileExpandedCalendarClassNames}
                        />
                        {data?.lastUpdatedAt && (
                            <p className="text-xs text-muted-foreground mt-3 text-center">
                                Обновлено: {formatLastUpdatedAt(data.lastUpdatedAt)}
                            </p>
                        )}
                    </>
                )}
            </CollapsibleContent>
        </Collapsible>
    );
}
