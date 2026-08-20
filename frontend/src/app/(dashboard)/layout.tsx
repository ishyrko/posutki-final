import { ApartmentCatalogSlugGate } from '@/components/ApartmentCatalogSlugGate';
import { OwnerFeaturesProvider } from '@/features/properties/OwnerFeaturesProvider';
import { fetchHasMyProperties } from '@/lib/my-properties-server';
import DashboardLayoutClient from './DashboardLayoutClient';

/** Кабинет всегда динамический — данные пользователя и объявлений с сервера. */
export const dynamic = 'force-dynamic';

export default async function DashboardLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const initialHasMyProperties = await fetchHasMyProperties();

    return (
        <ApartmentCatalogSlugGate>
            <OwnerFeaturesProvider initialHasMyProperties={initialHasMyProperties}>
                <DashboardLayoutClient>{children}</DashboardLayoutClient>
            </OwnerFeaturesProvider>
        </ApartmentCatalogSlugGate>
    );
}
