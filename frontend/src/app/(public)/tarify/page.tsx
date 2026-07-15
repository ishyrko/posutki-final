import type { Metadata } from 'next';
import { TariffsPageContent } from '@/features/placement/components/TariffsPageContent';

export const metadata: Metadata = {
    title: 'Тарифы и стоимость размещения — Posutki.by',
    description:
        'Спецразмещение и стандартное размещение объявлений на Posutki.by. Бесплатный пробный месяц для новых объявлений.',
};

export default function TariffsPage() {
    return (
        <section className="relative overflow-hidden bg-background py-16 md:py-20">
            <div className="absolute left-1/4 top-0 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
            <div className="absolute bottom-0 right-1/4 h-64 w-64 rounded-full bg-accent blur-3xl" />
            <div className="container relative mx-auto px-4">
                <TariffsPageContent />
            </div>
        </section>
    );
}
