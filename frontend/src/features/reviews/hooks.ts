'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    deleteReview,
    getMyReviews,
    getOwnerPropertyReviews,
    getOwnerReviews,
    getPropertyReviews,
    replyToReview,
    submitReview,
} from './api';

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
        mutationFn: (payload: { rating: number; text: string; shareDataWithOwner?: boolean }) =>
            submitReview(propertyId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['property-reviews', propertyId] });
            void queryClient.invalidateQueries({ queryKey: ['property', propertyId] });
            void queryClient.invalidateQueries({ queryKey: ['my-reviews'] });
        },
    });
}

export function useDeleteReview(propertyId?: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (reviewId: number) => deleteReview(reviewId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['my-reviews'] });
            if (propertyId) {
                void queryClient.invalidateQueries({ queryKey: ['property-reviews', propertyId] });
                void queryClient.invalidateQueries({ queryKey: ['property', propertyId] });
            }
        },
    });
}

export function useOwnerReviews() {
    return useQuery({
        queryKey: ['owner-reviews'],
        queryFn: getOwnerReviews,
    });
}

export function useMyReviews() {
    return useQuery({
        queryKey: ['my-reviews'],
        queryFn: getMyReviews,
    });
}

export function useOwnerPropertyReviews(propertyId?: number) {
    return useQuery({
        queryKey: ['owner-property-reviews', propertyId],
        queryFn: () => getOwnerPropertyReviews(propertyId!),
        enabled: propertyId != null && propertyId > 0,
    });
}

export function useReplyToReview(propertyId?: number) {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ reviewId, text }: { reviewId: number; text: string }) => replyToReview(reviewId, text),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['owner-reviews'] });
            if (propertyId) {
                void queryClient.invalidateQueries({ queryKey: ['owner-property-reviews', propertyId] });
                void queryClient.invalidateQueries({ queryKey: ['property-reviews', propertyId] });
            }
        },
    });
}
