'use client';

import { useMemo, useState } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { ArrowLeft, BarChart3, Eye, Heart, Phone } from 'lucide-react';
import { ResponsiveContainer, CartesianGrid, Line, LineChart, Tooltip, XAxis, YAxis, Legend, BarChart, Bar } from 'recharts';
import { usePropertyStats } from '@/features/properties/hooks';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type PeriodValue = '7' | '30' | '90';

function formatShortDate(isoDate: string): string {
    const date = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return isoDate;
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
    }).format(date);
}

export default function PropertyStatsPage() {
    const params = useParams<{ id: string }>();
    const propertyId = Number(params?.id ?? 0);
    const [period, setPeriod] = useState<PeriodValue>('30');
    const periodNumber = Number(period) as 7 | 30 | 90;

    const { data, isLoading, isError } = usePropertyStats(propertyId, periodNumber);

    const chartData = useMemo(
        () => (data?.daily ?? []).map((point) => ({ ...point, shortDate: formatShortDate(point.date) })),
        [data?.daily]
    );

    return (
        <div className="space-y-6 min-w-0">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                <div className="min-w-0">
                    <Link href="/kabinet/moi-obyavleniya/aktivnye/" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-2">
                        <ArrowLeft className="h-4 w-4" />
                        Назад к объявлениям
                    </Link>
                    <h1 className="text-2xl font-bold text-foreground break-words">
                        {data?.property.title || 'Статистика объявления'}
                    </h1>
                </div>

                <Select value={period} onValueChange={(v) => setPeriod(v as PeriodValue)}>
                    <SelectTrigger className="w-full shrink-0 sm:w-[180px]">
                        <SelectValue placeholder="Период" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="7">Последние 7 дней</SelectItem>
                        <SelectItem value="30">Последние 30 дней</SelectItem>
                        <SelectItem value="90">Последние 90 дней</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {isLoading && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="h-28 rounded-xl border border-border bg-card animate-pulse" />
                    ))}
                    <div className="md:col-span-3 h-80 rounded-xl border border-border bg-card animate-pulse" />
                    <div className="md:col-span-3 h-80 rounded-xl border border-border bg-card animate-pulse" />
                </div>
            )}

            {!isLoading && isError && (
                <Card>
                    <CardContent className="py-8 text-center text-muted-foreground">
                        Не удалось загрузить статистику. Проверьте доступ к объявлению.
                    </CardContent>
                </Card>
            )}

            {!isLoading && !isError && data && (
                <>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground inline-flex items-center gap-2">
                                    <Eye className="h-4 w-4" />
                                    Просмотры
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold">{data.totals.views}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground inline-flex items-center gap-2">
                                    <Phone className="h-4 w-4" />
                                    Показы телефона
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold">{data.totals.phoneViews}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground inline-flex items-center gap-2">
                                    <Heart className="h-4 w-4" />
                                    В избранное
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold">{data.totals.favorites}</p>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="inline-flex items-center gap-2">
                                <BarChart3 className="h-4 w-4" />
                                Динамика просмотров
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="min-w-0">
                            <div className="h-80 w-full min-w-0">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={chartData} margin={{ top: 8, right: 6, left: 4, bottom: 6 }}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="shortDate" tick={{ fontSize: 11 }} interval="preserveStartEnd" />
                                        <YAxis allowDecimals={false} width={36} tick={{ fontSize: 11 }} />
                                        <Tooltip />
                                        <Legend />
                                        <Line type="monotone" dataKey="views" stroke="hsl(var(--primary))" strokeWidth={2} name="Просмотры" />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Контакты и избранное по дням</CardTitle>
                        </CardHeader>
                        <CardContent className="min-w-0">
                            <div className="h-80 w-full min-w-0">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 8, right: 6, left: 4, bottom: 6 }}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="shortDate" tick={{ fontSize: 11 }} interval="preserveStartEnd" />
                                        <YAxis allowDecimals={false} width={36} tick={{ fontSize: 11 }} />
                                        <Tooltip />
                                        <Legend />
                                        <Bar dataKey="phoneViews" fill="hsl(var(--primary))" name="Показы телефона" />
                                        <Bar dataKey="favorites" fill="#475569" name="В избранном" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </>
            )}
        </div>
    );
}
