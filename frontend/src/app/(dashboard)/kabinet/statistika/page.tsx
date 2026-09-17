'use client';

import { useState } from 'react';
import { useMyPropertiesStats } from '@/features/properties/hooks';
import { PropertyStatsDashboard, type StatsPeriod } from '@/features/properties/components/PropertyStatsDashboard';

export default function MyPropertiesStatsPage() {
    const [period, setPeriod] = useState<StatsPeriod>('30');
    const periodNumber = Number(period) as 7 | 30 | 90;
    const { data, isLoading, isError } = useMyPropertiesStats(periodNumber);

    return (
        <PropertyStatsDashboard
            totals={data?.totals}
            daily={data?.daily}
            period={period}
            onPeriodChange={setPeriod}
            isLoading={isLoading}
            isError={isError}
            errorMessage="Не удалось загрузить статистику."
            header={
                <div className="min-w-0">
                    <h1 className="text-2xl font-bold text-foreground break-words">Статистика по всем объявлениям</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        учтено объявлений: {data?.propertiesCount ?? '—'}
                    </p>
                </div>
            }
        />
    );
}
