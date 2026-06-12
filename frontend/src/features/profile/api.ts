import api from '@/lib/api';
import { isAxiosError } from 'axios';
import { z } from 'zod';
import { User } from '@/features/auth/types';
import { FileTooLargeError } from '@/features/create-listing/api';
import { getTelegramUsernameError, normalizeTelegramUsername } from '@/lib/telegramUsername';

const telegramFieldSchema = z
    .string()
    .max(100)
    .optional()
    .superRefine((value, ctx) => {
        if (value === undefined || value.trim() === '') {
            return;
        }

        const error = getTelegramUsernameError(value);
        if (error) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: error,
            });
        }
    });

export const updateProfileSchema = z.object({
    name: z.string().min(2, 'Введите имя').max(100, 'Имя не длиннее 100 символов'),
    phone: z.string().optional(),
    avatar: z.string().optional(),
    telegram: telegramFieldSchema,
    phoneHasViber: z.boolean().optional(),
    phoneHasWhatsapp: z.boolean().optional(),
});

export type UpdateProfileData = z.infer<typeof updateProfileSchema>;

export const changePasswordSchema = z.object({
    currentPassword: z.string().min(1, 'Введите текущий пароль'),
    newPassword: z.string().min(6, 'Минимум 6 символов'),
    confirmPassword: z.string().min(6, 'Минимум 6 символов'),
}).refine(data => data.newPassword === data.confirmPassword, {
    message: 'Пароли не совпадают',
    path: ['confirmPassword'],
});

export type ChangePasswordData = z.infer<typeof changePasswordSchema>;

type UploadResponse = { url: string; thumbnailUrl?: string | null };

export const uploadAvatar = async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('scope', 'avatars');

    try {
        const response = await api.postForm<{ data: UploadResponse }>('/upload', formData);
        return response.data.data.url;
    } catch (error: unknown) {
        if (isAxiosError(error) && error.response?.status === 413) {
            throw new FileTooLargeError(file.name);
        }
        throw error;
    }
};

export const updateProfile = async (data: UpdateProfileData): Promise<User> => {
    const { avatar, telegram, ...rest } = data;
    const payload: Record<string, unknown> = { ...rest };
    const trimmedAvatar = avatar?.trim();
    if (trimmedAvatar) {
        payload.avatar = trimmedAvatar;
    }
    if (telegram !== undefined) {
        payload.telegram = normalizeTelegramUsername(telegram) ?? '';
    }
    const response = await api.put<User>('/users/profile', payload);
    return response.data;
};

export const changePassword = async (data: Omit<ChangePasswordData, 'confirmPassword'>): Promise<void> => {
    await api.post('/users/change-password', data);
};
