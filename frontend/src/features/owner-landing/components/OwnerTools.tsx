import Link from 'next/link';
import { CalendarSync, CreditCard, LayoutDashboard } from 'lucide-react';

const TOOLS = [
    {
        icon: CalendarSync,
        title: 'Интеграция с RealtyCalendar',
        desc: 'Синхронизация календаря занятости через iCal.',
        href: '/integratsiya-s-realty-calendar/',
    },
    {
        icon: CreditCard,
        title: 'Оплата размещения',
        desc: 'Способы оплаты VIP-уровней и продвижения объявлений.',
        href: '/oplata/',
    },
    {
        icon: LayoutDashboard,
        title: 'Личный кабинет',
        desc: 'Объявления, сообщения, статистика и оплаты - в одном месте.',
        href: '/kabinet/',
    },
];

export function OwnerTools() {
    return (
        <section className="py-12 md:py-16 bg-surface">
            <div className="container mx-auto px-4">
                <div className="mx-auto max-w-2xl text-center mb-10 md:mb-12">
                    <h2 className="font-display text-2xl font-bold text-foreground md:text-3xl">
                        Инструменты владельца
                    </h2>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {TOOLS.map((tool) => {
                        const Icon = tool.icon;
                        return (
                            <Link
                                key={tool.title}
                                href={tool.href}
                                className="group rounded-xl bg-card p-5 shadow-card transition-shadow hover:shadow-card-hover"
                            >
                                <Icon className="mb-3 h-6 w-6 text-primary" />
                                <h3 className="mb-1 font-semibold text-foreground group-hover:text-primary transition-colors">
                                    {tool.title}
                                </h3>
                                <p className="text-sm text-muted-foreground">{tool.desc}</p>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
