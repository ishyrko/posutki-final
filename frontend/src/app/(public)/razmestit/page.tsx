import { CreateListingForm } from '@/features/create-listing/components/CreateListingForm';

/** Форма подачи тяжёлая; не собираем статически при build. */
export const dynamic = 'force-dynamic';

export default function CreateListingPage() {
    return (
        <div className="container mx-auto max-w-3xl px-4 pt-10 pb-16">
            <CreateListingForm />
        </div>
    );
}
