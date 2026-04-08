'use client';

import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import { submitFeedback, SubmitFeedbackData } from './api';

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

export const useSubmitFeedback = () => {
    return useMutation({
        mutationFn: (data: SubmitFeedbackData) => submitFeedback(data),
        onSuccess: () => {
            toast.success('Сообщение отправлено. Мы скоро свяжемся с вами.');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отправить сообщение'));
        },
    });
};
