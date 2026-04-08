'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getPhones, requestVerification, verifyPhone, deletePhone } from './api';
import { toast } from 'sonner';
import { isAuthenticated } from '@/lib/auth';

function getErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }

    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }

    const message = (data as { message?: unknown }).message;
    return typeof message === 'string' && message.length > 0 ? message : fallback;
}

export const usePhones = () => {
    return useQuery({
        queryKey: ['phones'],
        queryFn: getPhones,
        enabled: isAuthenticated(),
    });
};

export const useRequestVerification = () => {
    return useMutation({
        mutationFn: (phone: string) => requestVerification(phone),
        onSuccess: () => {
            toast.success('Код отправлен');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отправить код'));
        },
    });
};

export const useVerifyPhone = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ phone, code }: { phone: string; code: string }) => verifyPhone(phone, code),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['phones'] });
            queryClient.invalidateQueries({ queryKey: ['me'] });
            toast.success('Телефон подтверждён');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Неверный код'));
        },
    });
};

export const useDeletePhone = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: number) => deletePhone(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['phones'] });
            toast.success('Телефон удалён');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось удалить'));
        },
    });
};
