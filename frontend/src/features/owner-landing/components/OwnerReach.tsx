import { Search, Megaphone, MousePointerClick } from 'lucide-react';

const POINTS = [
    {
        icon: Search,
        title: 'Сильное SEO',
        desc: 'Объявления индексируются поисковиками и находятся по запросам о посуточной аренде в Беларуси.',
    },
    {
        icon: Megaphone,
        title: 'Реклама на Беларусь и Россию',
        desc: 'Продвигаем каталог в Беларуси и России, чтобы к вашему жилью приходили гости из обоих рынков.',
    },
    {
        icon: MousePointerClick,
        title: 'Более 50 взаимодействий в месяц',
        desc: 'В среднем каждая квартира получает больше 50 просмотров телефонов, переходов в мессенджеры и заявок на бронирование за месяц.',
    },
];

export function OwnerReach() {
    return (
        <section className="py-12 md:py-16 bg-surface">
            <div className="container mx-auto px-4">
                <div className="mx-auto max-w-2xl text-center mb-10 md:mb-12">
                    <h2 className="font-display text-2xl font-bold text-foreground md:text-3xl">
                        Ваше объявление видят там, где ищут жильё
                    </h2>
                    <p className="mt-3 text-muted-foreground">
                        Поиск, реклама и стабильный поток интереса к каждой квартире.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-8 sm:grid-cols-3">
                    {POINTS.map((point) => {
                        const Icon = point.icon;
                        return (
                            <div key={point.title} className="text-center">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                                    <Icon className="h-7 w-7 text-primary" />
                                </div>
                                <h3 className="mb-2 font-display font-semibold text-foreground">
                                    {point.title}
                                </h3>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    {point.desc}
                                </p>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
