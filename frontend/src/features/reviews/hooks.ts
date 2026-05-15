'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deleteReview, getPropertyReviews, submitReview } from './api';

export function usePropertyReviews(propertyId: number) {
    return useQuery({
        queryKey: ['property-reviews', propertyId],
        queryFn: () => getPropertyReviews(propertyId),
        enabled: propertyId > 0,
    });
}

export function useSubmitReview(propertyId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: { rating: number; text?: string | null }) => submitReview(propertyId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['property-reviews', propertyId] });
            void queryClient.invalidateQueries({ queryKey: ['property', propertyId] });
        },
    });
}

export function useDeletePendingReview(propertyId: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (reviewId: number) => deleteReview(reviewId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['property-reviews', propertyId] });
            void queryClient.invalidateQueries({ queryKey: ['property', propertyId] });
        },
    });
}
