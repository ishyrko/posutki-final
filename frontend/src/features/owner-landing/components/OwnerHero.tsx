import Link from 'next/link';
import { CalendarClock, Gift, Handshake } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { OwnerLandingCta } from './OwnerLandingCta';

const TRUST_POINTS = [
    { icon: Gift, label: 'Размещение бесплатное' },
    { icon: CalendarClock, label: 'Бесплатный VIP 1 на 2 недели' },
    { icon: Handshake, label: 'Без комиссии с бронирования' },
];

export function OwnerHero() {
    return (
        <section className="relative overflow-hidden bg-background py-12 md:py-16">
            <div className="absolute left-1/4 top-0 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
            <div className="absolute bottom-0 right-1/4 h-64 w-64 rounded-full bg-accent blur-3xl" />

            <div className="container relative mx-auto px-4">
                <div className="mx-auto max-w-3xl text-center">
                    <h1 className="font-display text-3xl font-bold tracking-tight text-foreground md:text-5xl">
                        Сдать квартиру на сутки в Минске и по всей Беларуси
                    </h1>
                    <p className="mt-4 text-lg text-muted-foreground md:text-xl">
                        Разместите объявление о посуточной аренде на Posutki.by и получайте
                        обращения от гостей напрямую - без посредников и комиссии за бронь.
                    </p>

                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <Button
                            size="lg"
                            asChild
                            className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
                        >
                            <OwnerLandingCta placement="hero">
                                Разместить объявление бесплатно
                            </OwnerLandingCta>
                        </Button>
                        <Button size="lg" variant="outline" asChild>
                            <Link href="/tarify/">Смотреть тарифы</Link>
                        </Button>
                    </div>

                    <div className="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
                        {TRUST_POINTS.map((point) => {
                            const Icon = point.icon;
                            return (
                                <div
                                    key={point.label}
                                    className="flex items-center gap-2 text-sm font-medium text-foreground/80"
                                >
                                    <Icon className="h-4 w-4 text-primary" />
                                    {point.label}
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}
