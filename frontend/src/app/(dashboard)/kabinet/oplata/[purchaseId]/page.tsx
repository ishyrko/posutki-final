'use client';

import { useParams } from 'next/navigation';
import { PlacementPaymentPage } from '@/features/placement/components/PlacementPaymentPage';

export default function KabinetPaymentPage() {
    const params = useParams<{ purchaseId: string }>();
    const purchaseId = Number(params?.purchaseId ?? 0);

    return <PlacementPaymentPage purchaseId={Number.isFinite(purchaseId) ? purchaseId : 0} />;
}
