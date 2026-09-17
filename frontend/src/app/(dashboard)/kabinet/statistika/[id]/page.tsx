'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { ArrowLeft } from 'lucide-react';
import { usePropertyStats } from '@/features/properties/hooks';
import { PropertyStatsDashboard, type StatsPeriod } from '@/features/properties/components/PropertyStatsDashboard';

export default function PropertyStatsPage() {
    const params = useParams<{ id: string }>();
    const propertyId = Number(params?.id ?? 0);
    const [period, setPeriod] = useState<StatsPeriod>('30');
    const periodNumber = Number(period) as 7 | 30 | 90;

    const { data, isLoading, isError } = usePropertyStats(propertyId, periodNumber);

    return (
        <PropertyStatsDashboard
            totals={data?.totals}
            daily={data?.daily}
            period={period}
            onPeriodChange={setPeriod}
            isLoading={isLoading}
            isError={isError}
            header={
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 mb-2">
                        <Link
                            href="/kabinet/moi-obyavleniya/aktivnye/"
                            className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Назад к объявлениям
                        </Link>
                        <Link href="/kabinet/statistika/" className="text-sm text-muted-foreground hover:text-foreground">
                            Ко сводной статистике
                        </Link>
                    </div>
                    <h1 className="text-2xl font-bold text-foreground break-words">
                        {data?.property.title || 'Статистика объявления'}
                    </h1>
                </div>
            }
        />
    );
}
