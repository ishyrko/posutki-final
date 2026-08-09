import type { Metadata } from 'next';
import { CreateListingForm } from '@/features/create-listing/components/CreateListingForm';

/** Форма подачи тяжёлая; не собираем статически при build. */
export const dynamic = 'force-dynamic';

export const metadata: Metadata = {
    title: 'Разместить объявление о посуточной аренде квартиры или дома на Posutki.by',
    description:
        'Подайте объявление о посуточной аренде квартиры или дома на Posutki.by. Бесплатное размещение для владельцев жилья в Беларуси.',
};

export default function CreateListingPage() {
    return (
        <div className="container mx-auto max-w-3xl px-4 pt-10 pb-16">
            <CreateListingForm />
        </div>
    );
}
