import api from '@/lib/api';
import type { PropertyReviewsResponse } from './types';

export async function getPropertyReviews(propertyId: number): Promise<PropertyReviewsResponse> {
    const response = await api.get<{ data: PropertyReviewsResponse }>(`/properties/${propertyId}/reviews`);
    return response.data.data;
}

export async function submitReview(
    propertyId: number,
    payload: { rating: number; text?: string | null },
): Promise<{ id: number; status: string; message: string }> {
    const response = await api.post<{ data: { id: number; status: string; message: string } }>(
        `/properties/${propertyId}/reviews`,
        payload,
    );
    return response.data.data;
}

export async function deleteReview(reviewId: number): Promise<void> {
    await api.delete(`/reviews/${reviewId}`);
}
