import { OwnerReviewsPage } from '@/features/reviews/components/OwnerReviewsPage';

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function KabinetPropertyReviewsPage({ params }: PageProps) {
    const { id } = await params;
    const propertyId = Number(id);

    return <OwnerReviewsPage propertyId={Number.isFinite(propertyId) ? propertyId : undefined} />;
}
