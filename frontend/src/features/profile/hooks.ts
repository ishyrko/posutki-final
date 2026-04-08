'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateProfile, changePassword, UpdateProfileData, ChangePasswordData } from './api';
import { updateEmail } from '@/features/auth/api';
import { toast } from 'sonner';

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
    if (typeof message === 'string' && message.length > 0) {
        return message;
    }
    const nestedError = (data as { error?: { message?: unknown } }).error?.message;
    return typeof nestedError === 'string' && nestedError.length > 0 ? nestedError : fallback;
}

export const useUpdateProfile = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: UpdateProfileData) => updateProfile(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['me'] });
            toast.success('Профиль обновлён');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось обновить профиль'));
        }
    });
};

export const useUpdateEmail = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (email: string) => updateEmail(email),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['me'] });
            toast.success('Проверьте почту и перейдите по ссылке для подтверждения email.');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось сохранить email'));
        },
    });
};

export const useChangePassword = () => {
    return useMutation({
        mutationFn: ({ currentPassword, newPassword }: ChangePasswordData) =>
            changePassword({ currentPassword, newPassword }),
        onSuccess: () => {
            toast.success('Пароль изменён');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось изменить пароль'));
        }
    });
}
