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
import type { PropertyReviewsResponse, ViewerReview } from './types';

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
        onSuccess: (data) => {
            const viewerReview = { id: data.id, status: data.status as ViewerReview['status'] };
            queryClient.setQueryData<PropertyReviewsResponse>(['property-reviews', propertyId], (old) =>
                old ? { ...old, viewerReview } : old,
            );
            queryClient.setQueryData(['property', propertyId], (old: { viewerReview?: ViewerReview | null } | undefined) =>
                old ? { ...old, viewerReview } : old,
            );
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
            if (propertyId) {
                queryClient.setQueryData<PropertyReviewsResponse>(['property-reviews', propertyId], (old) =>
                    old ? { ...old, viewerReview: null } : old,
                );
                queryClient.setQueryData(['property', propertyId], (old: { viewerReview?: ViewerReview | null } | undefined) =>
                    old ? { ...old, viewerReview: null } : old,
                );
            }
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
