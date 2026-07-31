import {
    CalendarDays,
    BarChart3,
    MessageSquare,
    RefreshCw,
    ShieldCheck,
    CreditCard,
} from 'lucide-react';

const BENEFITS = [
    {
        icon: CalendarDays,
        title: 'Календарь занятости',
        desc: 'Отмечайте занятые даты в личном кабинете, чтобы не получать заявки на уже забронированные дни.',
    },
    {
        icon: BarChart3,
        title: 'Статистика объявления',
        desc: 'Видите, сколько человек просматривает и открывает контакты по каждому объявлению.',
    },
    {
        icon: MessageSquare,
        title: 'Сообщения и заявки',
        desc: 'Заявки на бронирование и сообщения от гостей приходят в один раздел кабинета.',
    },
    {
        icon: RefreshCw,
        title: 'Синхронизация с RealtyCalendar',
        desc: 'Подключите iCal, чтобы календарь занятости не расходился между сервисами.',
    },
    {
        icon: ShieldCheck,
        title: 'Модерация объявлений',
        desc: 'Каждое объявление проверяется перед публикацией — это повышает доверие гостей.',
    },
    {
        icon: CreditCard,
        title: 'Оплата картой и ЕРИП',
        desc: 'Платное продвижение объявления оплачивается онлайн, без лишних шагов.',
    },
];

export function OwnerBenefits() {
    return (
        <section className="py-12 md:py-16 bg-background">
            <div className="container mx-auto px-4">
                <div className="mx-auto max-w-2xl text-center mb-10 md:mb-12">
                    <h2 className="font-display text-2xl font-bold text-foreground md:text-3xl">
                        Всё для управления объявлением в одном кабинете
                    </h2>
                </div>

                <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    {BENEFITS.map((benefit) => {
                        const Icon = benefit.icon;
                        return (
                            <div key={benefit.title} className="text-center">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                                    <Icon className="h-7 w-7 text-primary" />
                                </div>
                                <h3 className="mb-2 font-display font-semibold text-foreground">
                                    {benefit.title}
                                </h3>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    {benefit.desc}
                                </p>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
