'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { setToken } from '@/lib/auth';
import { requestSmsCode, verifySmsCode } from './api';

function getErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }

    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }

    const nestedError = (data as { error?: { message?: unknown } }).error?.message;
    if (typeof nestedError === 'string' && nestedError.length > 0) {
        return nestedError;
    }

    const message = (data as { message?: unknown }).message;
    return typeof message === 'string' && message.length > 0 ? message : fallback;
}

export const useRequestSmsCode = () => {
    return useMutation({
        mutationFn: (phone: string) => requestSmsCode(phone),
        onSuccess: () => {
            toast.success('Код отправлен');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отправить код'));
        },
    });
};

export const useVerifySmsCode = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ phone, code }: { phone: string; code: string }) => verifySmsCode(phone, code),
        onSuccess: (token) => {
            setToken(token);
            queryClient.invalidateQueries({ queryKey: ['me'] });
            queryClient.invalidateQueries({ queryKey: ['phones'] });
            toast.success('Вход выполнен');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Неверный код'));
        },
    });
};
