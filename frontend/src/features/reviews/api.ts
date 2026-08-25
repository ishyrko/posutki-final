import api from '@/lib/api';
import type {
    OwnerPropertyReviewsResponse,
    OwnerReviewsResponse,
    PropertyReviewsResponse,
} from './types';

export async function getPropertyReviews(propertyId: number): Promise<PropertyReviewsResponse> {
    const response = await api.get<{ data: PropertyReviewsResponse }>(`/properties/${propertyId}/reviews`);
    return response.data.data;
}

export async function submitReview(
    propertyId: number,
    payload: { rating: number; text: string; shareDataWithOwner?: boolean },
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

export async function getOwnerReviews(): Promise<OwnerReviewsResponse> {
    const response = await api.get<{ data: OwnerReviewsResponse }>('/owner/reviews');
    return response.data.data;
}

export async function getOwnerPropertyReviews(propertyId: number): Promise<OwnerPropertyReviewsResponse> {
    const response = await api.get<{ data: OwnerPropertyReviewsResponse }>(`/owner/properties/${propertyId}/reviews`);
    return response.data.data;
}

export async function replyToReview(reviewId: number, text: string): Promise<{ ownerReply: string; ownerRepliedAt: string }> {
    const response = await api.post<{ data: { ownerReply: string; ownerRepliedAt: string } }>(
        `/reviews/${reviewId}/reply`,
        { text },
    );
    return response.data.data;
}
